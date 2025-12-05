<?php

namespace App\Http\Controllers;

use App\Models\Pemesanan;
use App\Models\BookingKamar;
use App\Models\PemesananLayanan;
use App\Models\CartItem;
use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CancelOrderController extends Controller
{
    /**
     * Cancel an order and return items to cart
     * POST /order/cancel/{pemesanan_id}
     */
    public function cancel(Request $request, $pemesanan_id)
    {
        $user = $request->user();
        $pemesanan = Pemesanan::find($pemesanan_id);

        if (! $pemesanan) {
            return response()->json(['success' => false, 'message' => 'Pesanan not found'], 404);
        }

        // Verify ownership
        if ($pemesanan->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Check if order can be cancelled (not already completed/confirmed)
        if (in_array($pemesanan->status_pemesanan, ['completed', 'confirmed', 'cancelled'])) {
            return response()->json([
                'success' => false,
                'message' => 'Pesanan tidak dapat dibatalkan karena status: ' . $pemesanan->status_pemesanan,
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Recreate cart items from booking entries
            $cart = Cart::firstOrCreate(
                ['user_id' => $user->id, 'is_checked_out' => false],
                ['user_id' => $user->id, 'is_checked_out' => false]
            );

            // Get booked kamars and recreate cart items
            $bookingKamars = BookingKamar::where('pemesanan_id', $pemesanan_id)->get();
            foreach ($bookingKamars as $bk) {
                CartItem::create([
                    'cart_id' => $cart->id,
                    'item_id' => $bk->kamar_id,
                    'item_type' => 'App\Models\Kamar',
                    'jumlah' => 1,
                    'harga_satuan' => $bk->total_harga, // simplified; ideally store original price
                    'total_harga' => $bk->total_harga,
                    'tanggal_checkin' => $bk->tanggal_checkin,
                    'tanggal_checkout' => $bk->tanggal_checkout,
                    'checked' => true,
                ]);
            }

            // Get pesanan layanans and recreate cart items
            $pesananLayanans = PemesananLayanan::where('pemesanan_id', $pemesanan_id)->get();
            foreach ($pesananLayanans as $pl) {
                CartItem::create([
                    'cart_id' => $cart->id,
                    'item_id' => $pl->layanan_id,
                    'item_type' => 'App\Models\Layanan',
                    'jumlah' => $pl->jumlah ?? 1,
                    'harga_satuan' => $pl->harga_satuan,
                    'total_harga' => $pl->total_harga,
                    'checked' => true,
                ]);
            }

            // Update pemesanan status to cancelled
            $pemesanan->update(['status_pemesanan' => 'cancelled']);

            // Delete booking entries
            BookingKamar::where('pemesanan_id', $pemesanan_id)->delete();
            PemesananLayanan::where('pemesanan_id', $pemesanan_id)->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pesanan berhasil dibatalkan. Item dikembalikan ke keranjang.',
                'data' => [
                    'pemesanan_id' => $pemesanan_id,
                    'status' => 'cancelled',
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat membatalkan pesanan: ' . $e->getMessage(),
            ], 500);
        }
    }
}
