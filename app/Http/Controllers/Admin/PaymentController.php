<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\invoice;
use App\Models\Pembayaran;
use App\Models\Pemesanan;
use Illuminate\Support\Facades\Http;

class PaymentController extends Controller
{
    protected function authorizeAdmin(Request $request)
    {
        $user = $request->user();
        if (!$user || $user->role !== 'admin') {
            abort(403, 'Forbidden');
        }
    }

    public function index(Request $request)
    {
        $this->authorizeAdmin($request);

        $query = invoice::with(['pemesanan', 'user']);
        $perPage = (int) $request->query('per_page', 15);
        $data = $query->orderBy('created_at', 'desc')->paginate($perPage);

        $this->syncPendingInvoicesWithMidtrans($data->items());

        // reload after sync so response already reflects latest status
        $data = $query->orderBy('created_at', 'desc')->paginate($perPage);
        return response()->json($data);
    }

    public function show(Request $request, $id)
    {
        $this->authorizeAdmin($request);
        $inv = invoice::with(['pemesanan.bookingKamars.kamar', 'user'])->find($id);
        if (!$inv) return response()->json(['message' => 'Not found'], 404);
        unset($inv->petugas_id);
        if (isset($inv->petugas)) unset($inv->petugas);
        return response()->json(['data' => $inv]);
    }

    private function syncPendingInvoicesWithMidtrans(array $invoices): void
    {
        $serverKey = config('midtrans.server_key') ?: env('MIDTRANS_SERVER_KEY');
        if (!$serverKey) return;

        $base = config('midtrans.base_url', 'https://api.sandbox.midtrans.com');

        foreach ($invoices as $inv) {
            $current = strtolower((string) ($inv->status_pembayaran ?? ''));
            if (!in_array($current, ['pending', 'wait', 'waiting_payment', ''])) {
                continue;
            }

            $orderId = $inv->order_id;
            if (!$orderId) {
                $pay = Pembayaran::where('pemesanan_id', $inv->pemesanan_id)->latest()->first();
                $orderId = $pay?->order_id;
            }
            if (!$orderId) continue;

            try {
                $resp = Http::withBasicAuth($serverKey, '')->get($base . '/v2/' . $orderId . '/status');
                if (!$resp->successful()) continue;

                $body = $resp->json();
                $txn = strtolower((string) ($body['transaction_status'] ?? 'pending'));
                $fraud = strtolower((string) ($body['fraud_status'] ?? ''));

                $mapped = 'pending';
                if ($txn === 'settlement' || ($txn === 'capture' && $fraud === 'accept')) {
                    $mapped = 'lunas';
                } elseif (in_array($txn, ['expire'])) {
                    $mapped = 'expired';
                } elseif (in_array($txn, ['cancel', 'deny', 'failure'])) {
                    $mapped = 'failed';
                }

                $amount = isset($body['gross_amount']) ? (float) $body['gross_amount'] : (float) ($inv->total_amount ?? 0);
                $updateInvoice = [
                    'order_id' => $orderId,
                    'status_pembayaran' => $mapped,
                ];
                if ($mapped === 'lunas') {
                    $updateInvoice['jumlah_bayar'] = $amount;
                    $updateInvoice['succeded_at'] = now()->toDateString();
                }
                $inv->update($updateInvoice);

                $pembayaran = Pembayaran::where('pemesanan_id', $inv->pemesanan_id)->latest()->first();
                if ($pembayaran) {
                    $pembayaran->update([
                        'order_id' => $orderId,
                        'status' => $txn,
                        'total' => $amount > 0 ? $amount : $pembayaran->total,
                    ]);
                }

                $pemesanan = Pemesanan::find($inv->pemesanan_id);
                if ($pemesanan) {
                    $statusPemesanan = $pemesanan->status_pemesanan;
                    if ($mapped === 'lunas') $statusPemesanan = 'confirmed';
                    elseif (in_array($mapped, ['expired', 'failed'])) $statusPemesanan = 'cancelled';

                    $pemesanan->update([
                        'status_pembayaran' => $mapped,
                        'status_pemesanan' => $statusPemesanan,
                    ]);
                }
            } catch (\Throwable $e) {
                // keep list endpoint resilient; skip failed sync
                continue;
            }
        }
    }
}
