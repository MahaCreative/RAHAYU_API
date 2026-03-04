<?php

namespace App\Http\Controllers;

use App\Models\Pembayaran;
use App\Models\Pemesanan;
use App\Models\invoice as InvoiceModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PaymentController extends Controller
{
    // create virtual account via Midtrans Core API (bank_transfer)
    public function createVirtualAccount(Request $request)
    {
        $request->validate([
            'pemesanan_id' => 'required|integer|exists:pemesanans,id',
            'bank' => 'required|string',
        ]);

        $pemesanan = Pemesanan::with('user')->find($request->pemesanan_id);
        if (! $pemesanan) return response()->json(['success' => false, 'message' => 'Pemesanan not found'], 404);

        $amount = (int) round($pemesanan->total_harga);

        $serverKey = config('midtrans.server_key') ?: env('MIDTRANS_SERVER_KEY');
        if (! $serverKey) return response()->json(['success' => false, 'message' => 'Midtrans server key not configured'], 500);

        $payload = [
            'payment_type' => 'bank_transfer',
            'transaction_details' => [
                'order_id' => 'ORDER-' . $pemesanan->id . '-' . time(),
                'gross_amount' => $amount,
            ],
            'bank_transfer' => [
                'bank' => $request->bank,
            ],
            'customer_details' => [
                'first_name' => $pemesanan->user->first_name,
                'email' => $pemesanan->user->email,
            ],
            // Tambahkan expiry agar tidak langsung expired
            'custom_expiry' => [
                'order_time' => date('Y-m-d H:i:s O'),
                'expiry_duration' => 1440, // 24 jam
                'unit' => 'minutes',
            ],
        ];

        $base = config('midtrans.base_url', 'https://api.sandbox.midtrans.com');
        $endpoint = $base . '/v2/charge';
        $resp = Http::withBasicAuth($serverKey, '')->post($endpoint, $payload);

        if (! $resp->successful()) {
            return response()->json(['success' => false, 'message' => 'Midtrans error', 'details' => $resp->body()], 500);
        }

        $data = $resp->json();

        // determine VA number and expiry (Midtrans returns va_numbers array or 'permata_va_number')
        $va = null;
        if (isset($data['va_numbers']) && count($data['va_numbers'])) {
            $va = $data['va_numbers'][0]['va_number'];
            $bank = $data['va_numbers'][0]['bank'] ?? $request->bank;
        } elseif (isset($data['permata_va_number'])) {
            $va = $data['permata_va_number'];
            $bank = 'permata';
        } else {
            $va = $data['payment_code'] ?? null;
            $bank = $request->bank;
        }

        // Use Midtrans-provided expiry_time only. Do NOT fall back to transaction_time
        // because transaction_time is the creation time and would make the payment
        // immediately expire when used as expiry.
        $expiry = $data['expiry_time'] ?? null;
        // Save pembayaran with order_id if provided
        $orderId = $data['order_id'] ?? ($payload['transaction_details']['order_id'] ?? null);

        $pembayaran = Pembayaran::create([
            'pemesanan_id' => $pemesanan->id,
            'va_number' => $va,
            'bank' => $bank,
            'total' => $amount,
            'status' => 'pending',
            'expiry' => $expiry ? date('Y-m-d H:i:s', strtotime($expiry)) : null,
            'order_id' => $orderId,
        ]);

        // update pemesanan record with VA info
        $pemesanan->update([
            'status_pemesanan' => 'waiting_payment',
        ]);

        $this->syncInvoiceFromPayment(
            $pemesanan,
            $orderId,
            (float) $amount,
            'pending',
            'bank_transfer',
            null,
            $va
        );

        return response()->json(['success' => true, 'data' => ['pembayaran' => $pembayaran, 'midtrans' => $data]]);
    }

    // create e-wallet payment via Midtrans Core API (gopay, shopeepay, dana etc.)
    public function createEwallet(Request $request)
    {
        $request->validate([
            'pemesanan_id' => 'required|integer|exists:pemesanans,id',
            'payment_type' => 'required|string', // e.g. gopay, shopeepay, dana
        ]);

        $pemesanan = Pemesanan::with('user')->find($request->pemesanan_id);
        if (! $pemesanan) return response()->json(['success' => false, 'message' => 'Pemesanan not found'], 404);

        $amount = (int) round($pemesanan->total_harga);
        $serverKey = config('midtrans.server_key') ?: env('MIDTRANS_SERVER_KEY');
        if (! $serverKey) return response()->json(['success' => false, 'message' => 'Midtrans server key not configured'], 500);

        // Map frontend codes to Midtrans supported payment types
        $paymentTypeMap = [
            'gopay' => 'gopay',
            'dana' => 'gopay', // Dana mapped to gopay
            'shopeepay' => 'shopeepay',
        ];
        $paymentType = $paymentTypeMap[$request->payment_type] ?? $request->payment_type;

        $orderId = 'ORDER-' . $pemesanan->id . '-' . time();
        $payload = [
            'payment_type' => $paymentType,
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $amount,
            ],
            'customer_details' => [
                'first_name' => $pemesanan->user->first_name,
                'email' => $pemesanan->user->email,
            ],
            'custom_expiry' => [
                'order_time' => date('Y-m-d H:i:s O'),
                'expiry_duration' => 1440, // 24 jam
                'unit' => 'minutes',
            ],
        ];

        // Add payment-specific config for deeplink/QR
        if ($paymentType === 'gopay') {
            $payload['gopay'] = [
                'callback_url' => url('/api/midtrans/callback'),
                'enable_callback' => true,
            ];
        } elseif ($paymentType === 'shopeepay') {
            $payload['shopeepay'] = [
                'callback_url' => url('/api/midtrans/callback'),
            ];
        }

        $base = config('midtrans.base_url', 'https://api.sandbox.midtrans.com');
        $endpoint = $base . '/v2/charge';
        $resp = Http::withBasicAuth($serverKey, '')->post($endpoint, $payload);

        if (! $resp->successful()) {
            return response()->json(['success' => false, 'message' => 'Midtrans error', 'details' => $resp->body()], 500);
        }

        $data = $resp->json();

        // Extract QR code and redirect URL from Midtrans response
        $paymentInfo = [];
        $qrCodeUrl = null;
        $redirectUrl = null;

        // GoPay/Dana: extract QR code (qr_string is base64 encoded PNG)
        if ($request->payment_type === "gopay" || $request->payment_type === "dana") {

            // Ambil QR Code URL dari actions
            if (!empty($data['actions'])) {
                foreach ($data['actions'] as $action) {
                    if ($action['name'] === 'generate-qr-code') {
                        $qrCodeUrl = $action['url']; // <— Ini QR yang benar
                    }
                    if ($action['name'] === 'deeplink-redirect' || $action['name'] === 'open_url') {
                        $redirectUrl = $action['url'];
                    }
                }
            }

            $paymentInfo = [
                'qr_code' => $qrCodeUrl,
                'redirect_url' => $redirectUrl,
            ];
        }
        // ShopeePay: extract redirect URL
        else if ($request->payment_type === "shopeepay") {
            if (!empty($data['actions'])) {
                foreach ($data['actions'] as $action) {
                    if ($action['name'] === 'open_url') {
                        $redirectUrl = $action['url'] ?? null;
                        break;
                    }
                }
            }

            $paymentInfo = [
                'redirect_url' => $redirectUrl,
            ];
        }

        $paymentInfo['expiry_time'] = $data['expiry_time'] ?? null;

        $pembayaran = Pembayaran::create([
            'pemesanan_id' => $pemesanan->id,
            'va_number' => json_encode($paymentInfo), // Store payment_info as JSON
            'bank' => $request->payment_type, // Store original e-wallet name
            'total' => $amount,
            'status' => 'pending',
            'expiry' => $data['expiry_time'] ? date('Y-m-d H:i:s', strtotime($data['expiry_time'])) : null,
            'order_id' => $orderId,
        ]);

        $pemesanan->update(['status_pemesanan' => 'waiting_payment']);

        $this->syncInvoiceFromPayment(
            $pemesanan,
            $orderId,
            (float) $amount,
            'pending',
            $request->payment_type,
            $paymentInfo,
            null
        );

        // Return response with full details
        return response()->json([
            'success' => true,
            'data' => [
                'pembayaran' => $pembayaran,
                'payment_info' => $paymentInfo,
                'midtrans' => $data,
            ],
        ]);
    }

    public function showPaymentDetail($pemesanan_id)
    {
        $pemesanan = Pemesanan::with('bookingKamars', 'pesananLayanans')->find($pemesanan_id);
        if (! $pemesanan) return response()->json(['success' => false, 'message' => 'Pemesanan not found'], 404);

        $pembayaran = Pembayaran::where('pemesanan_id', $pemesanan_id)->latest()->first();

        // Parse payment_info if it's stored as JSON
        $paymentInfo = null;
        if ($pembayaran && $pembayaran->va_number) {
            $paymentInfo = json_decode($pembayaran->va_number, true);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'pemesanan' => $pemesanan,
                'pembayaran' => $pembayaran,
                'payment_info' => $paymentInfo,
            ],
        ]);
    }

    // Cancel payment (like in your old code)
    public function cancelPayment(Request $request, $orderId)
    {
        $pembayaran = Pembayaran::where('order_id', $orderId)->first();
        if (! $pembayaran) {
            return response()->json(['success' => false, 'message' => 'Payment not found'], 404);
        }

        $serverKey = config('midtrans.server_key') ?: env('MIDTRANS_SERVER_KEY');
        if (! $serverKey) {
            return response()->json(['success' => false, 'message' => 'Midtrans server key not configured'], 500);
        }

        // Call Midtrans cancel API
        $base = config('midtrans.base_url', 'https://api.sandbox.midtrans.com');
        $endpoint = $base . '/v2/' . $orderId . '/cancel';
        $resp = Http::withBasicAuth($serverKey, '')->post($endpoint);

        // Update local status
        $pembayaran->update(['status' => 'cancelled']);

        $pemesanan = Pemesanan::find($pembayaran->pemesanan_id);
        if ($pemesanan) {
            $pemesanan->update(['status_pemesanan' => 'cancelled']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment cancelled successfully',
            'data' => $resp->json(),
        ]);
    }

    // Handle Midtrans callback (like in your old code)
    public function handleCallback(Request $request)
    {
        $orderId = $request->order_id;
        $statusCode = $request->status_code;
        $grossAmount = (float) $request->gross_amount;
        $signatureKey = $request->signature_key;
        $transactionStatus = $request->transaction_status;
        $fraudStatus = $request->fraud_status ?? null;
        $settlementTime = $request->settlement_time ?? null;

        $pembayaran = Pembayaran::where('order_id', $orderId)->first();
        if (! $pembayaran) {
            return response()->json(['success' => false, 'message' => 'Payment not found'], 404);
        }

        $serverKey = config('midtrans.server_key') ?: env('MIDTRANS_SERVER_KEY');
        $hash = hash('sha512', $orderId . $statusCode . $request->gross_amount . $serverKey);
        if ($signatureKey !== $hash) {
            return response()->json(['success' => false, 'message' => 'Invalid signature'], 401);
        }

        $pembayaran->update([
            'status' => $transactionStatus,
            'expiry' => $settlementTime ? date('Y-m-d H:i:s', strtotime($settlementTime)) : null,
        ]);

        $statusPemesanan = 'waiting_payment';
        $statusPembayaran = 'pending';
        $jumlahBayar = 0;

        if ($transactionStatus === 'settlement') {
            $statusPemesanan = 'confirmed';
            $statusPembayaran = 'lunas';
            $jumlahBayar = $grossAmount;
        } elseif ($transactionStatus === 'capture' && $fraudStatus === 'accept') {
            $statusPemesanan = 'confirmed';
            $statusPembayaran = 'lunas';
            $jumlahBayar = $grossAmount;
        } elseif ($transactionStatus === 'expire') {
            $statusPemesanan = 'expired';
            $statusPembayaran = 'expired';
        } elseif (in_array($transactionStatus, ['cancel', 'deny', 'failure'])) {
            $statusPemesanan = 'failed';
            $statusPembayaran = 'failed';
        }

        $pemesanan = Pemesanan::with([
            'bookingKamars',
            'pesananLayanans',
        ])->find($pembayaran->pemesanan_id);

        if ($pemesanan) {
            $totalHarga = (float) ($pemesanan->total_harga ?? 0);
            $paidAmount = $statusPembayaran === 'lunas' ? $jumlahBayar : (float) ($pemesanan->jumlah_bayar ?? 0);
            $pemesanan->update([
                'status_pembayaran' => $statusPembayaran,
                'status_pemesanan' => $statusPemesanan,
                'jumlah_bayar' => $paidAmount,
                'sisa_bayar' => max(0, $totalHarga - $paidAmount),
                'waktu_konfirmasi' => $statusPembayaran === 'lunas'
                    ? date('Y-m-d H:i:s', strtotime($settlementTime ?: now()))
                    : $pemesanan->waktu_konfirmasi,
            ]);

            foreach ($pemesanan->bookingKamars as $bookingKamar) {
                $bkTotal = (float) ($bookingKamar->total_harga ?? 0);
                $bookingKamar->update([
                    'status_booking' => 'pending',
                    'jumlah_bayar' => $jumlahBayar,
                    'sisa_bayar' => max(0, $bkTotal - $jumlahBayar),
                    'status_pembayaran' => $statusPembayaran,
                ]);
            }

            foreach ($pemesanan->pesananLayanans as $pesananLayanan) {
                $layananTotal = (float) ($pesananLayanan->total_harga ?? 0);
                $pesananLayanan->update([
                    'status_pemesanan' => 'pending',
                    'jumlah_bayar' => $jumlahBayar,
                    'sisa_bayar' => max(0, $layananTotal - $jumlahBayar),
                    'tanggal_bayar' => $statusPembayaran === 'lunas'
                        ? date('Y-m-d H:i:s', strtotime($settlementTime ?: now()))
                        : $pesananLayanan->tanggal_bayar,
                    'status_pembayaran' => $statusPembayaran,
                ]);
            }
        }

        $this->syncInvoiceFromPayment(
            $pemesanan,
            $orderId,
            $grossAmount,
            $statusPembayaran,
            $request->payment_type ?? $pembayaran->bank,
            null,
            $pembayaran->va_number
        );

        return response()->json([
            'success' => true,
            'message' => 'Callback processed',
            'midtrans_status' => $transactionStatus,
            'mapped_status' => $statusPemesanan,
        ]);
    }

    private function syncInvoiceFromPayment(
        ?Pemesanan $pemesanan,
        ?string $orderId,
        float $amount,
        string $invoiceStatus,
        ?string $paymentType,
        $paymentInfo,
        ?string $paymentCode
    ): void {
        if (! $pemesanan) {
            return;
        }

        $invoice = InvoiceModel::where('pemesanan_id', $pemesanan->id)->latest()->first();
        $jumlahBayar = $invoiceStatus === 'lunas' ? $amount : 0;

        if (! $invoice) {
            InvoiceModel::create([
                'invoice_number' => strtoupper(uniqid('INV')),
                'petugas_id' => null,
                'pemesanan_id' => $pemesanan->id,
                'user_id' => $pemesanan->user_id,
                'order_id' => $orderId ?: ('ORDER-' . $pemesanan->id . '-' . time()),
                'total_amount' => $amount,
                'jumlah_bayar' => $jumlahBayar,
                'payment_type' => $paymentType,
                'payment_info' => is_array($paymentInfo) ? json_encode($paymentInfo) : $paymentInfo,
                'payment_code' => $paymentCode,
                'status_pembayaran' => $invoiceStatus,
                'succeded_at' => $invoiceStatus === 'lunas' ? now()->toDateString() : null,
            ]);
            return;
        }

        $updatePayload = [
            'order_id' => $orderId ?: $invoice->order_id,
            'total_amount' => $invoice->total_amount > 0 ? $invoice->total_amount : $amount,
            'jumlah_bayar' => $invoiceStatus === 'lunas' ? $amount : $invoice->jumlah_bayar,
            'payment_type' => $paymentType ?: $invoice->payment_type,
            'payment_code' => $paymentCode ?: $invoice->payment_code,
            'status_pembayaran' => $invoiceStatus,
        ];

        if ($paymentInfo) {
            $updatePayload['payment_info'] = is_array($paymentInfo)
                ? json_encode($paymentInfo)
                : $paymentInfo;
        }

        if ($invoiceStatus === 'lunas') {
            $updatePayload['succeded_at'] = now()->toDateString();
        }

        $invoice->update($updatePayload);
    }
}
