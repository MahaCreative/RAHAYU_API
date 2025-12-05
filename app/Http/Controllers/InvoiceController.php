<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pemesanan;
use Barryvdh\DomPDF\Facade\Pdf as PDF;

class InvoiceController extends Controller
{
    public function show(Request $request, $pemesanan_id)
    {
        $user = $request->user();
        if (! $user) return response()->json(['message' => 'Unauthenticated'], 401);

        $pemesanan = Pemesanan::where('id', $pemesanan_id)
            ->where('user_id', $user->id)
            ->with(['bookingKamars.kamar', 'pesananLayanans.layanan', 'invoice', 'pembayarans'])
            ->first();

        if (! $pemesanan) return response()->json(['message' => 'Pemesanan not found'], 404);

        if ($pemesanan->invoice) {
            unset($pemesanan->invoice->petugas_id);
            if (isset($pemesanan->invoice->petugas)) unset($pemesanan->invoice->petugas);
        }

        return response()->json(['success' => true, 'data' => $pemesanan]);
    }

    public function pdf(Request $request, $pemesanan_id)
    {
        $user = $request->user();
        if (! $user) return response()->json(['message' => 'Unauthenticated'], 401);

        $pemesanan = Pemesanan::where('id', $pemesanan_id)
            ->where('user_id', $user->id)
            ->with(['bookingKamars.kamar', 'pesananLayanans.layanan', 'invoice', 'pembayarans', 'user'])
            ->first();

        if (! $pemesanan) return response()->json(['message' => 'Pemesanan not found'], 404);

        $data = ['pemesanan' => $pemesanan];

        try {
            $pdf = PDF::loadView('invoices.template', $data)->setPaper('a4', 'portrait');
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'PDF generator not available', 'error' => $e->getMessage()], 500);
        }

        $filename = 'invoice-' . ($pemesanan->invoice->invoice_number ?? $pemesanan->kode_pemesanan ?? $pemesanan->id) . '.pdf';

        return $pdf->download($filename);
    }
}
