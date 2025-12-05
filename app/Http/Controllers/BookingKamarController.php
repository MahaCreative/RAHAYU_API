<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BookingKamar;
use Illuminate\Support\Facades\Hash;

class BookingKamarController extends Controller
{
    // Return a signed QR payload for a booking_kamar (customer must own the pemesanan)
    public function qr(Request $request, $id)
    {
        $user = $request->user();
        if (! $user) return response()->json(['message' => 'Unauthenticated'], 401);

        $bk = BookingKamar::with('pemesanan')->find($id);
        if (! $bk) return response()->json(['message' => 'Not found'], 404);

        if ($bk->pemesanan->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // Create payload
        $payload = [
            'booking_kamar_id' => $bk->id,
            'pemesanan_id' => $bk->pemesanan_id,
            'ts' => now()->timestamp,
        ];

        $data = base64_encode(json_encode($payload));
        $signature = hash_hmac('sha256', $data, config('app.key'));

        return response()->json(['data' => $data, 'signature' => $signature]);
    }
}
