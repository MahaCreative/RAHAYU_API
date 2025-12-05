<?php

namespace App\Http\Controllers;

use App\Models\Pembayaran;
use App\Models\Pemesanan;
use Illuminate\Http\Request;

class MidtransCallbackController extends Controller
{
    public function handle(Request $request)
    {
        // Midtrans will POST transaction status here
        $payload = $request->all();

        // verify signature_key if present
        $serverKey = config('midtrans.server_key') ?: env('MIDTRANS_SERVER_KEY');
        if ($serverKey && isset($payload['signature_key'])) {
            $orderId = $payload['order_id'] ?? '';
            $statusCode = $payload['status_code'] ?? '';
            $grossAmount = $payload['gross_amount'] ?? ($payload['gross_amount'] ?? '');
            $expected = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);
            if (! hash_equals($expected, $payload['signature_key'])) {
                return response()->json(['success' => false, 'message' => 'Invalid signature'], 403);
            }
        }

        // basic handling: find pembayaran by order_id or va_number
        $orderId = $payload['order_id'] ?? null;
        $transactionStatus = $payload['transaction_status'] ?? ($payload['status_code'] ?? null);
        $fraudStatus = $payload['fraud_status'] ?? null;

        // try to locate payment by order_id
        $pembayaran = null;
        if ($orderId) {
            $pembayaran = Pembayaran::where('order_id', $orderId)->first();
        }

        // fallback by va_number
        if (! $pembayaran && isset($payload['va_number'])) {
            $pembayaran = Pembayaran::where('va_number', $payload['va_number'])->first();
        }

        if (! $pembayaran) {
            return response()->json(['success' => false, 'message' => 'Pembayaran not found'], 404);
        }

        // Update based on status (simplified)
        if (in_array($transactionStatus, ['settlement', 'capture', 'success'])) {
            $pembayaran->status = 'settlement';
            $pembayaran->save();

            $pemesanan = Pemesanan::find($pembayaran->pemesanan_id);
            if ($pemesanan) {
                $pemesanan->update(['status_pemesanan' => 'confirmed', 'jumlah_bayar' => $pembayaran->total, 'status_pembayaran' => 'LUNAS']);
            }
        } elseif (in_array($transactionStatus, ['expire', 'cancel', 'deny'])) {
            $pembayaran->status = 'cancelled';
            $pembayaran->save();
            $pemesanan = Pemesanan::find($pembayaran->pemesanan_id);
            if ($pemesanan) {
                $pemesanan->update(['status_pemesanan' => 'cancelled']);
            }
        }

        return response()->json(['success' => true]);
    }
}
