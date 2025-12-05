<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pemesanan;
use Illuminate\Http\JsonResponse;

class PemesananController extends Controller
{
    protected function authorizeAdmin(Request $request)
    {
        $user = $request->user();
        if (!$user || $user->role !== 'admin') {
            abort(403, 'Forbidden');
        }
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        $query = Pemesanan::query()->with(['bookingKamars.kamar', 'pesananLayanans.layanan', 'invoice', 'user']);

        if ($request->filled('status')) {
            $status = $request->query('status');
            if ($status === 'cancelled_done') {
                $query->whereIn('status_pemesanan', ['cancelled', 'done']);
            } else {
                $query->where('status_pemesanan', $status);
            }
        }

        $perPage = (int) $request->query('per_page', 15);
        $data = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // Remove petugas_id from relations
        $data->getCollection()->transform(function ($item) {
            $item->bookingKamars = $item->bookingKamars->map(function ($bk) {
                unset($bk->petugas_id);
                if (isset($bk->petugas)) unset($bk->petugas);
                return $bk;
            });
            if ($item->invoice) {
                unset($item->invoice->petugas_id);
                if (isset($item->invoice->petugas)) unset($item->invoice->petugas);
            }
            return $item;
        });

        return response()->json($data);
    }

    public function show(Request $request, $id): JsonResponse
    {
        $this->authorizeAdmin($request);

        $pemesanan = Pemesanan::with(['bookingKamars.kamar', 'pesananLayanans.layanan', 'invoice', 'user'])
            ->find($id);

        if (!$pemesanan) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $pemesanan->bookingKamars = $pemesanan->bookingKamars->map(function ($bk) {
            unset($bk->petugas_id);
            if (isset($bk->petugas)) unset($bk->petugas);
            return $bk;
        });
        if ($pemesanan->invoice) {
            unset($pemesanan->invoice->petugas_id);
            if (isset($pemesanan->invoice->petugas)) unset($pemesanan->invoice->petugas);
        }

        return response()->json(['data' => $pemesanan]);
    }

    public function export(Request $request)
    {
        $this->authorizeAdmin($request);

        $query = Pemesanan::with(['bookingKamars.kamar', 'pesananLayanans.layanan', 'invoice', 'user']);
        if ($request->filled('status')) {
            $status = $request->query('status');
            if ($status === 'cancelled_done') {
                $query->whereIn('status_pemesanan', ['cancelled', 'done']);
            } else {
                $query->where('status_pemesanan', $status);
            }
        }

        $rows = $query->orderBy('created_at', 'desc')->get();

        $callback = function () use ($rows) {
            $out = fopen('php://output', 'w');
            // header
            fputcsv($out, ['ID', 'Kode', 'User', 'Status', 'Total Harga', 'Jumlah Bayar', 'Sisa Bayar', 'Waktu Pemesanan']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->id,
                    $r->kode_pemesanan,
                    $r->user->name ?? $r->user->email ?? '-',
                    $r->status_pemesanan,
                    $r->total_harga,
                    $r->jumlah_bayar,
                    $r->sisa_bayar,
                    $r->waktu_pemesanan,
                ]);
            }
            fclose($out);
        };

        $fileName = 'pemesanan_report_' . date('Ymd_His') . '.csv';
        return response()->streamDownload($callback, $fileName, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
