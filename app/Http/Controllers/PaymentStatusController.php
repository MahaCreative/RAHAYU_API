<?php

namespace App\Http\Controllers;

use App\Models\Pembayaran;
use App\Models\Pemesanan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PaymentStatusController extends Controller
{
    /**
     * Check payment status from Midtrans
     * GET /payment/status/{pemesanan_id}
     */
    public function check($pemesanan_id)
    {
        $pemesanan = Pemesanan::find($pemesanan_id);
        if (! $pemesanan) {
            return response()->json(['success' => false, 'message' => 'Pemesanan not found'], 404);
        }

        $pembayaran = Pembayaran::where('pemesanan_id', $pemesanan_id)->latest()->first();
        if (! $pembayaran) {
            return response()->json(['success' => false, 'message' => 'Pembayaran not found'], 404);
        }

        // If we already have status recorded locally, return it
        // In a real scenario, you'd query Midtrans for the latest status
        if ($pembayaran->status && $pembayaran->status !== 'pending') {
            return response()->json([
                'success' => true,
                'data' => [
                    'status' => $pembayaran->status,
                    'order_id' => $pembayaran->order_id,
                    'va_number' => $pembayaran->va_number,
                    'message' => $this->getStatusMessage($pembayaran->status),
                ],
            ]);
        }

        // Query Midtrans for latest status
        $serverKey = config('midtrans.server_key') ?: env('MIDTRANS_SERVER_KEY');
        if (! $serverKey) {
            return response()->json([
                'success' => true,
                'data' => [
                    'status' => $pembayaran->status,
                    'message' => 'Status: ' . ucfirst($pembayaran->status),
                ],
            ]);
        }

        $orderId = $pembayaran->order_id;
        if (! $orderId) {
            return response()->json([
                'success' => true,
                'data' => [
                    'status' => $pembayaran->status,
                    'message' => 'Status: ' . ucfirst($pembayaran->status),
                ],
            ]);
        }

        $base = config('midtrans.base_url', 'https://api.sandbox.midtrans.com');
        $endpoint = $base . '/v2/' . $orderId . '/status';

        try {
            $resp = Http::withBasicAuth($serverKey, '')->get($endpoint);
            if ($resp->successful()) {
                $data = $resp->json();
                $txnStatus = $data['transaction_status'] ?? 'unknown';

                // Map Midtrans status to our status
                $status = $this->mapMidtransStatus($txnStatus);

                // Update local record
                $pembayaran->update(['status' => $status]);

                // Update pemesanan status if needed
                if ($status === 'settlement') {
                    $pemesanan->update(['status_pemesanan' => 'confirmed']);
                } elseif (in_array($status, ['expire', 'cancel', 'deny'])) {
                    $pemesanan->update(['status_pemesanan' => 'cancelled']);
                }

                return response()->json([
                    'success' => true,
                    'data' => [
                        'status' => $status,
                        'order_id' => $orderId,
                        'va_number' => $pembayaran->va_number,
                        'message' => $this->getStatusMessage($status),
                    ],
                ]);
            }
        } catch (\Exception $e) {
            // Fall back to local status
        }

        return response()->json([
            'success' => true,
            'data' => [
                'status' => $pembayaran->status,
                'message' => 'Status: ' . ucfirst($pembayaran->status),
            ],
        ]);
    }

    private function mapMidtransStatus($txnStatus)
    {
        $map = [
            'settlement' => 'settlement',
            'capture' => 'settlement',
            'pending' => 'pending',
            'expire' => 'expire',
            'cancel' => 'cancel',
            'deny' => 'deny',
        ];
        return $map[$txnStatus] ?? 'pending';
    }

    private function getStatusMessage($status)
    {
        $messages = [
            'settlement' => 'Pembayaran berhasil! Pesanan Anda telah dikonfirmasi.',
            'pending' => 'Pembayaran masih tertunda. Silakan selesaikan transfer.',
            'expire' => 'Pembayaran telah kadaluarsa. Buat pesanan baru.',
            'cancel' => 'Pembayaran dibatalkan.',
            'deny' => 'Pembayaran ditolak. Coba metode pembayaran lain.',
        ];
        return $messages[$status] ?? 'Status pembayaran tidak diketahui.';
    }
}
