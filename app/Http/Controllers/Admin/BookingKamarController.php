<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BookingKamar;
use App\Models\Tamu;

class BookingKamarController extends Controller
{
    protected function authorizeAdmin(Request $request)
    {
        $user = $request->user();
        if (!$user || $user->role !== 'admin') {
            abort(403, 'Forbidden');
        }
    }

    // Confirm checkin: accept guests array and mark waktu_checkin
    public function confirmCheckin(Request $request, $id)
    {
        $this->authorizeAdmin($request);

        $bk = BookingKamar::find($id);
        if (! $bk) return response()->json(['message' => 'Not found'], 404);

        // If scan payload provided, verify signature
        $data = $request->input('data');
        $signature = $request->input('signature');
        if ($data && $signature) {
            $expected = hash_hmac('sha256', $data, config('app.key'));
            if (! hash_equals($expected, $signature)) {
                return response()->json(['message' => 'Invalid QR signature'], 403);
            }
            $payload = json_decode(base64_decode($data), true);
            if (! $payload || ($payload['booking_kamar_id'] ?? null) != $id) {
                return response()->json(['message' => 'Invalid QR payload'], 403);
            }
            // freshness: 20 minutes
            if (abs(now()->timestamp - ($payload['ts'] ?? 0)) > 60 * 20) {
                return response()->json(['message' => 'QR expired'], 410);
            }
        }

        $guests = $request->input('guests', []);

        // Validate count
        if (! is_array($guests) || count($guests) !== (int) $bk->jumlah_tamu) {
            return response()->json(['message' => 'Jumlah tamu tidak sesuai dengan booking'], 422);
        }

        // Create guest records
        $created = [];
        foreach ($guests as $g) {
            $t = Tamu::create([
                'nama' => $g['nama'] ?? null,
                'nik' => $g['nik'] ?? null,
                'jenis_kelamin' => $g['jenis_kelamin'] ?? null,
                'jenis_identitas' => $g['jenis_identitas'] ?? null,
                'kamar_id' => $g['kamar_id'] ?? $bk->kamar_id,
                'booking_kamar_id' => $bk->id,
            ]);
            $created[] = $t;
        }

        $bk->waktu_checkin = now();
        $bk->status_booking = 'checked_in';
        $bk->save();

        return response()->json(['success' => true, 'guests' => $created]);
    }

    // Confirm checkout: mark waktu_checkout and status finished
    public function confirmCheckout(Request $request, $id)
    {
        $this->authorizeAdmin($request);

        $bk = BookingKamar::find($id);
        if (! $bk) return response()->json(['message' => 'Not found'], 404);

        $bk->waktu_checkout = now();
        $bk->status_booking = 'checked_out';
        $bk->save();

        return response()->json(['success' => true, 'booking_kamar' => $bk]);
    }
}
