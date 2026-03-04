<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BookingKamar;
use App\Models\Tamu;
use App\Models\invoice as InvoiceModel;
use App\Models\Pembayaran;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    protected function authorizeAdmin(Request $request)
    {
        $user = $request->user();
        if (!$user || $user->role !== 'admin') {
            abort(403, 'Forbidden');
        }
    }

    // Booking Kamar report with filters: start_date, end_date, kamar_id, status
    public function bookingReport(Request $request)
    {
        $this->authorizeAdmin($request);

        $q = BookingKamar::query()->with([
            'pemesanan.user',
            'kamar',
            'tamu:id,booking_kamar_id,nama',
        ]);

        if ($request->filled('kamar_id')) {
            $q->where('kamar_id', $request->query('kamar_id'));
        }
        if ($request->filled('status')) {
            $q->where('status_booking', $request->query('status'));
        }
        if ($request->filled('start_date')) {
            $q->whereDate('tanggal_checkin', '>=', $request->query('start_date'));
        }
        if ($request->filled('end_date')) {
            $q->whereDate('tanggal_checkout', '<=', $request->query('end_date'));
        }

        $data = $q->orderBy('tanggal_checkin', 'desc')->paginate($request->query('per_page', 50));
        $data->getCollection()->transform(function ($row) {
            $names = collect($row->tamu ?? [])
                ->pluck('nama')
                ->map(fn ($n) => trim((string) $n))
                ->filter()
                ->values();
            $fallbackName = trim((string) ($row->pemesanan->user->name ?? ''));
            if ($fallbackName === '') {
                $fallbackName = trim((string) ($row->pemesanan->user->email ?? ''));
            }
            $row->nama_tamu = $names->isNotEmpty()
                ? $names->implode(', ')
                : ($fallbackName !== '' ? $fallbackName : '-');
            return $row;
        });

        if ($request->query('export') === 'csv') {
            $rows = $q->orderBy('tanggal_checkin', 'desc')->get();
            $callback = function () use ($rows) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['ID', 'Kode Booking', 'User', 'Kamar', 'Nama Tamu', 'Checkin', 'Checkout', 'Jumlah Tamu', 'Status', 'Waktu Checkin', 'Waktu Checkout']);
                foreach ($rows as $r) {
                    $namaTamu = $r->tamu->pluck('nama')->filter()->implode(', ');
                    if (!$namaTamu) {
                        $namaTamu = $r->pemesanan->user->name ?? $r->pemesanan->user->email ?? '-';
                    }
                    fputcsv($out, [
                        $r->id,
                        $r->kode_booking,
                        $r->pemesanan->user->name ?? $r->pemesanan->user->email ?? '-',
                        $r->kamar->nama ?? $r->kamar->nomor_kamar ?? '-',
                        $namaTamu ?: '-',
                        $r->tanggal_checkin,
                        $r->tanggal_checkout,
                        $r->jumlah_tamu,
                        $r->status_booking,
                        $r->waktu_checkin,
                        $r->waktu_checkout,
                    ]);
                }
                fclose($out);
            };
            $fileName = 'report_booking_' . date('Ymd_His') . '.csv';
            return response()->streamDownload($callback, $fileName, ['Content-Type' => 'text/csv']);
        }

        return response()->json($data);
    }

    // Tamu report with filters: start_date, end_date, kamar_id
    public function tamuReport(Request $request)
    {
        $this->authorizeAdmin($request);

        $q = Tamu::query()->with(['bookingKamar.kamar', 'bookingKamar.pemesanan', 'kamar']);

        if ($request->filled('kamar_id')) {
            $q->where('kamar_id', $request->query('kamar_id'));
        }
        if ($request->filled('start_date')) {
            $q->whereHas('bookingKamar', function ($qq) use ($request) {
                $qq->whereDate('tanggal_checkin', '>=', $request->query('start_date'));
            });
        }
        if ($request->filled('end_date')) {
            $q->whereHas('bookingKamar', function ($qq) use ($request) {
                $qq->whereDate('tanggal_checkout', '<=', $request->query('end_date'));
            });
        }

        $data = $q->orderBy('created_at', 'desc')->paginate($request->query('per_page', 100));

        if ($request->query('export') === 'csv') {
            $rows = $q->orderBy('created_at', 'desc')->get();
            $callback = function () use ($rows) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['ID', 'Nama', 'NIK', 'Jenis Kelamin', 'Jenis Identitas', 'Kamar', 'Kode Booking', 'Checkin', 'Checkout']);
                foreach ($rows as $r) {
                    $bk = $r->bookingKamar;
                    fputcsv($out, [
                        $r->id,
                        $r->nama,
                        $r->nik,
                        $r->jenis_kelamin,
                        $r->jenis_identitas,
                        $r->kamar->nama ?? $r->kamar->nomor_kamar ?? '-',
                        $bk->kode_booking ?? '-',
                        $bk->tanggal_checkin ?? '-',
                        $bk->tanggal_checkout ?? '-',
                    ]);
                }
                fclose($out);
            };
            $fileName = 'report_tamu_' . date('Ymd_His') . '.csv';
            return response()->streamDownload($callback, $fileName, ['Content-Type' => 'text/csv']);
        }

        return response()->json($data);
    }

    // Finance report: summary of invoices/payments between dates, optional export
    public function financeReport(Request $request)
    {
        $this->authorizeAdmin($request);

        $start = $request->query('start_date');
        $end = $request->query('end_date');

        $q = InvoiceModel::query()->with(['pemesanan.user']);
        if ($start) $q->whereDate('created_at', '>=', $start);
        if ($end) $q->whereDate('created_at', '<=', $end);

        $rows = $q->orderBy('created_at', 'desc')->paginate($request->query('per_page', 50));
        $rowPemesananIds = collect($rows->items())
            ->pluck('pemesanan_id')
            ->filter()
            ->unique()
            ->values();

        $paidStatuses = ['settlement', 'capture', 'paid', 'success', 'lunas'];
        $paidMapForRows = [];
        if ($rowPemesananIds->isNotEmpty()) {
            $paidMapForRows = Pembayaran::query()
                ->select('pemesanan_id', DB::raw('SUM(total) as paid_total'))
                ->whereIn('pemesanan_id', $rowPemesananIds)
                ->whereIn('status', $paidStatuses)
                ->groupBy('pemesanan_id')
                ->pluck('paid_total', 'pemesanan_id')
                ->toArray();
        }

        $rows->getCollection()->transform(function ($inv) use ($paidMapForRows) {
            $invoicePaid = (float) ($inv->jumlah_bayar ?? 0);
            $fallbackPaid = (float) ($paidMapForRows[$inv->pemesanan_id] ?? 0);
            $effectivePaid = $invoicePaid > 0 ? $invoicePaid : $fallbackPaid;
            $inv->jumlah_bayar_efektif = $effectivePaid;
            $inv->status_pembayaran_efektif = $effectivePaid > 0
                ? 'lunas'
                : ($inv->status_pembayaran ?? 'pending');
            return $inv;
        });

        if ($request->query('export') === 'csv') {
            $all = $q->orderBy('created_at', 'desc')->get();
            $allPemesananIds = $all->pluck('pemesanan_id')->filter()->unique()->values();
            $paidMapAll = [];
            if ($allPemesananIds->isNotEmpty()) {
                $paidMapAll = Pembayaran::query()
                    ->select('pemesanan_id', DB::raw('SUM(total) as paid_total'))
                    ->whereIn('pemesanan_id', $allPemesananIds)
                    ->whereIn('status', $paidStatuses)
                    ->groupBy('pemesanan_id')
                    ->pluck('paid_total', 'pemesanan_id')
                    ->toArray();
            }
            $callback = function () use ($all, $paidMapAll) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Invoice', 'Pemesanan', 'User', 'Total Amount', 'Jumlah Bayar', 'Created At']);
                foreach ($all as $a) {
                    $invoicePaid = (float) ($a->jumlah_bayar ?? 0);
                    $fallbackPaid = (float) ($paidMapAll[$a->pemesanan_id] ?? 0);
                    $effectivePaid = $invoicePaid > 0 ? $invoicePaid : $fallbackPaid;
                    fputcsv($out, [
                        $a->invoice_number ?? '-',
                        $a->pemesanan_id ?? '-',
                        $a->pemesanan->user->name ?? '-',
                        $a->total_amount ?? 0,
                        $effectivePaid,
                        $a->created_at,
                    ]);
                }
                fclose($out);
            };
            $fileName = 'report_finance_' . date('Ymd_His') . '.csv';
            return response()->streamDownload($callback, $fileName, ['Content-Type' => 'text/csv']);
        }

        // also return summary totals
        $totalsQuery = InvoiceModel::query();
        if ($start) $totalsQuery->whereDate('created_at', '>=', $start);
        if ($end) $totalsQuery->whereDate('created_at', '<=', $end);
        $totalAmount = (float) $totalsQuery->sum('total_amount');

        $allForSummary = $q->get(['id', 'pemesanan_id', 'jumlah_bayar']);
        $summaryPemesananIds = $allForSummary->pluck('pemesanan_id')->filter()->unique()->values();
        $paidMapSummary = [];
        if ($summaryPemesananIds->isNotEmpty()) {
            $paidMapSummary = Pembayaran::query()
                ->select('pemesanan_id', DB::raw('SUM(total) as paid_total'))
                ->whereIn('pemesanan_id', $summaryPemesananIds)
                ->whereIn('status', $paidStatuses)
                ->groupBy('pemesanan_id')
                ->pluck('paid_total', 'pemesanan_id')
                ->toArray();
        }
        $totalPaid = (float) $allForSummary->sum(function ($inv) use ($paidMapSummary) {
            $invoicePaid = (float) ($inv->jumlah_bayar ?? 0);
            $fallbackPaid = (float) ($paidMapSummary[$inv->pemesanan_id] ?? 0);
            return $invoicePaid > 0 ? $invoicePaid : $fallbackPaid;
        });

        return response()->json(['data' => $rows, 'summary' => ['total_amount' => $totalAmount, 'total_paid' => $totalPaid]]);
    }
}
