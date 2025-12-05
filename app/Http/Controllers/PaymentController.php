<?php

namespace App\Http\Controllers;

use App\Models\Pembayaran;
use App\Models\Pemesanan;
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
        $grossAmount = $request->gross_amount;
        $signatureKey = $request->signature_key;
        $transactionStatus = $request->transaction_status;
        $fraudStatus = $request->fraud_status ?? null;
        $settlementTime = $request->settlement_time ?? null;

        $pembayaran = Pembayaran::where('order_id', $orderId)->first();
        if (! $pembayaran) {
            return response()->json(['success' => false, 'message' => 'Payment not found'], 404);
        }

        // Verify signature
        $serverKey = config('midtrans.server_key') ?: env('MIDTRANS_SERVER_KEY');
        $hash = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);
        if ($signatureKey !== $hash) {
            return response()->json(['success' => false, 'message' => 'Invalid signature'], 401);
        }

        /**
         * 📌 1. Update status pembayaran
         */
        $pembayaran->update([
            'status' => $transactionStatus,
            'expiry' => $settlementTime ? date('Y-m-d H:i:s', strtotime($settlementTime)) : null,
        ]);

        /**
         * 📌 2. Tentukan status pemesanan sesuai status Midtrans
         */
        $statusPemesanan = 'waiting_payment'; // default

        if ($transactionStatus === 'settlement') {
            $statusPemesanan = 'success';
        } elseif ($transactionStatus === 'capture' && $fraudStatus === 'accept') {
            $statusPemesanan = 'success';
        } elseif ($transactionStatus === 'expire') {
            $statusPemesanan = 'expired';
        } elseif (
            in_array($transactionStatus, ['cancel', 'deny', 'failure'])
        ) {
            $statusPemesanan = 'failed';
        }

        /**
         * 📌 3. Update pemesanan
         */
        $pemesanan = Pemesanan::with([
            'bookingKamars',
            'pesananLayanans',
        ])->find($pembayaran->pemesanan_id);

        if ($pemesanan) {
            $pemesanan->update([
                'status_pembayaran' => 'lunas',
                'status_pemesanan' => $statusPemesanan,
                'tanggal_bayar' => $transactionStatus === 'settlement'
                    ? date('Y-m-d H:i:s', strtotime($settlementTime))
                    : null,
            ]);
        }
        if (count($pemesanan->bookingKamars) > 0) {
            foreach ($pemesanan->bookingKamars as $bookingKamar) {
                $bookingKamar->update([
                    'status_booking' => 'pending',
                    'jumlah_bayar' => $request->gross_amount,
                    'status_pembayaran' => 'lunas'
                ]);
            }
        }
        if (count($pemesanan->bookingKamars) > 0) {
            foreach ($pemesanan->pesananLayanans as $pesananLayanan) {
                $pesananLayanan->update([
                    'status_pemesanan' => 'pending',
                    'jumlah_bayar' => $request->gross_amount,
                    'status_pembayaran' => 'lunas'
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Callback processed',
            'midtrans_status' => $transactionStatus,
            'mapped_status' => $statusPemesanan
        ]);
    }
}
