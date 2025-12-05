<?php

namespace App\Http\Controllers\Costumer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pemesanan;
use Illuminate\Http\JsonResponse;

use Illuminate\Support\Facades\Auth;

class PemesananController extends Controller
{
    /**
     * List pemesanan for authenticated customer. Optional query param `status`:
     * pending, confirmed, cancelled, done, or cancelled_done (maps to cancelled|done).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $query = Pemesanan::where('user_id', $user->id);

        $status = $request->query('status');
        if ($status) {
            if ($status === 'cancelled_done') {
                $query->whereIn('status_pemesanan', ['cancelled', 'done']);
            } else {
                $query->where('status_pemesanan', $status);
            }
        }

        $pemesanan = $query->with(['bookingKamars.kamar', 'bookingKamars.tamu', 'invoice'])->orderBy('created_at', 'desc')->get();

        // Remove petugas_id from bookingKamars before returning
        $data = $pemesanan->map(function ($item) {
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

        return response()->json(['data' => $data]);
    }

    /**
     * Show pemesanan detail (only for owner). Returns related bookingKamars (without petugas_id) and pesananLayanans with layanan.
     */
    public function show(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $pemesanan = Pemesanan::where('id', $id)->where('user_id', $user->id)
            ->with(['bookingKamars.kamar', 'bookingKamars.tamu', 'pesananLayanans.layanan', 'invoice', 'pembayarans'])
            ->first();

        if (!$pemesanan) {
            return response()->json(['message' => 'Pemesanan not found'], 404);
        }

        // sanitize bookingKamars and invoice: remove petugas_id/petugas
        $pemesanan->bookingKamars = $pemesanan->bookingKamars->map(function ($bk) {
            unset($bk->petugas_id);
            if (isset($bk->petugas)) unset($bk->petugas);
            return $bk;
        });
        if ($pemesanan->invoice) {
            unset($pemesanan->invoice->petugas_id);
            if (isset($pemesanan->invoice->petugas)) unset($pemesanan->invoice->petugas);
        }

        // sanitize pembayarans (if present)
        if ($pemesanan->pembayarans) {
            $pemesanan->pembayarans = $pemesanan->pembayarans->map(function ($pay) {
                // remove any internal fields that shouldn't be exposed
                if (isset($pay->created_at)) unset($pay->created_at);
                if (isset($pay->updated_at)) unset($pay->updated_at);
                return $pay;
            });
        }

        return response()->json(['data' => $pemesanan]);
    }

    /**
     * Store a direct pemesanan (no cart) from customer
     * Expected payload: { kamar_id, tanggal_checkin, tanggal_checkout, jumlah_tamu, catatan }
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $data = $request->validate([
            'kamar_id' => 'required|integer|exists:kamars,id',
            'tanggal_checkin' => 'required|date_format:Y-m-d',
            'tanggal_checkout' => 'required|date_format:Y-m-d|after:tanggal_checkin',
            'jumlah_tamu' => 'required|integer|min:1',
            'catatan' => 'nullable|string',
        ]);

        // compute nights and total
        $d1 = new \DateTime($data['tanggal_checkin']);
        $d2 = new \DateTime($data['tanggal_checkout']);
        $nights = (int) $d2->diff($d1)->days;

        $kamar = \App\Models\Kamar::find($data['kamar_id']);
        $harga = $kamar->harga_kamar ?? 0;
        $totalHarga = $harga * $nights;

        // create pemesanan
        $pemesanan = Pemesanan::create([
            'user_id' => $user->id,
            'kode_pemesanan' => strtoupper(uniqid('PMN')),
            'total_harga' => $totalHarga,
            'status_pemesanan' => 'pending',
            'waktu_pemesanan' => now(),
            'jumlah_bayar' => 0,
            'sisa_bayar' => $totalHarga,
            'status_pembayaran' => 'belum lunas',
        ]);

        // create booking_kamar
        $bk = \App\Models\BookingKamar::create([
            'kode_booking' => strtoupper(uniqid('BKG')),
            'pemesanan_id' => $pemesanan->id,
            'kamar_id' => $data['kamar_id'],
            'tanggal_checkin' => $data['tanggal_checkin'],
            'tanggal_checkout' => $data['tanggal_checkout'],
            'jumlah_tamu' => $data['jumlah_tamu'],
            'total_harga' => $totalHarga,
            'status_booking' => 'pending',
            'catatan_booking' => $data['catatan'] ?? null,
            'waktu_booking' => now(),
            'status_pembayaran' => 'belum lunas',
        ]);

        $pemesanan->load(['bookingKamars.kamar', 'pesananLayanans.layanan', 'invoice']);

        // remove petugas_id from relations
        $pemesanan->bookingKamars = $pemesanan->bookingKamars->map(function ($b) {
            unset($b->petugas_id);
            if (isset($b->petugas)) unset($b->petugas);
            return $b;
        });

        if ($pemesanan->invoice) {
            unset($pemesanan->invoice->petugas_id);
            if (isset($pemesanan->invoice->petugas)) unset($pemesanan->invoice->petugas);
        }

        return response()->json(['success' => true, 'data' => $pemesanan]);
    }
}
