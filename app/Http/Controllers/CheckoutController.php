<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Pemesanan;
use App\Models\BookingKamar;
use App\Models\PemesananLayanan;
use App\Models\invoice;

class CheckoutController extends Controller
{
    /**
     * Process checkout for the authenticated user's cart.
     * If `cart_item_ids` is provided it will checkout only those items,
     * otherwise it will checkout items marked as checked in the cart.
     */
    public function processCheckout(Request $request)
    {
        $user = $request->user();
        if (! $user) return response()->json(['message' => 'Unauthenticated'], 401);

        $request->validate([
            'cart_item_ids' => 'nullable|array',
            'cart_item_ids.*' => 'integer',
        ]);

        $cart = Cart::where('user_id', $user->id)->with('items')->first();

        if (! $cart || $cart->items->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Cart is empty'], 400);
        }

        $ids = $request->input('cart_item_ids');
        $items = $cart->items;
        if (is_array($ids) && count($ids)) {
            $items = $items->whereIn('id', $ids);
        } else {
            // fallback to items flagged as checked
            $items = $items->where('checked', true);
        }

        if ($items->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No items selected for checkout'], 400);
        }


        DB::beginTransaction();
        try {
            $total = $items->sum('total_harga');

            $pemesanan = Pemesanan::create([
                'user_id' => $user->id,
                'kode_pemesanan' => 'ORD-' . strtoupper(substr(md5(uniqid()), 0, 8)),
                'total_harga' => $total,
                'status_pemesanan' => 'pending',
                'waktu_pemesanan' => now(),
            ]);

            foreach ($items as $ci) {
                $type = strtolower($ci->item_type ?? '');

                if ($type == 'app\\models\\kamar') {
                    BookingKamar::create([
                        'pemesanan_id' => $pemesanan->id,
                        'kamar_id' => $ci->item_id,
                        'tanggal_checkin' => $ci->tanggal_checkin ?? now(),
                        'tanggal_checkout' => $ci->tanggal_checkout ?? now()->addDay(),
                        'jumlah_tamu' => $ci->jumlah ?? 1,
                        'total_harga' => $ci->total_harga,
                        'waktu_booking' => now(),
                        'status_booking' => 'pending',
                    ]);
                } elseif ($type == 'app\\models\\layanan') {
                    PemesananLayanan::create([
                        'pemesanan_id' => $pemesanan->id,
                        'layanan_id' => $ci->item_id,
                        'jumlah' => $ci->jumlah ?? 1,
                        'total_harga' => $ci->total_harga,
                    ]);
                }
            }

            // create invoice (petugas will be assigned later by staff)
            invoice::create([
                'invoice_number' => strtoupper(uniqid('INV')),
                'petugas_id' => null,
                'order_id' => null,
                'pemesanan_id' => $pemesanan->id,
                'user_id' => $user->id,
                'total_amount' => $total,
                'jumlah_bayar' => 0,
                'status_pembayaran' => 'pending',
            ]);

            // remove processed cart items
            $removeIds = $items->pluck('id')->toArray();
            CartItem::whereIn('id', $removeIds)->delete();

            // if cart is empty now delete it
            $remaining = CartItem::where('cart_id', $cart->id)->count();
            if ($remaining == 0) {
                $cart->delete();
            }

            DB::commit();

            $pemesanan->load('bookingKamars.kamar', 'pesananLayanans.layanan', 'invoice');
            return response()->json(['success' => true, 'data' => $pemesanan]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Checkout selected cart items for authenticated user.
     * Expects `cart_item_ids` array in request body.
     */
    public function checkoutSelected(Request $request)
    {
        $user = $request->user();
        if (! $user) return response()->json(['message' => 'Unauthenticated'], 401);

        $data = $request->validate([
            'cart_item_ids' => 'required|array|min:1',
            'cart_item_ids.*' => 'integer|exists:cart_items,id',
        ]);

        $ids = $data['cart_item_ids'];

        $items = CartItem::whereIn('id', $ids)
            ->whereHas('cart', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })->get();

        if ($items->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No cart items found for checkout'], 400);
        }

        DB::beginTransaction();
        try {
            $total = $items->sum('total_harga');

            $pemesanan = Pemesanan::create([
                'user_id' => $user->id,
                'kode_pemesanan' => 'ORD-' . strtoupper(substr(md5(uniqid()), 0, 8)),
                'total_harga' => $total,
                'status_pemesanan' => 'pending',
                'waktu_pemesanan' => now(),
            ]);

            foreach ($items as $it) {
                $type = strtolower($it->item_type ?? '');
                if ($type === 'kamar') {
                    BookingKamar::create([
                        'pemesanan_id' => $pemesanan->id,
                        'kamar_id' => $it->item_id,
                        'tanggal_checkin' => $it->tanggal_checkin ?? now(),
                        'tanggal_checkout' => $it->tanggal_checkout ?? now()->addDay(),
                        'jumlah_tamu' => $it->jumlah ?? 1,
                        'total_harga' => $it->total_harga,
                        'waktu_booking' => now(),
                        'status_booking' => 'pending',
                    ]);
                } elseif ($type === 'layanan') {
                    PemesananLayanan::create([
                        'pemesanan_id' => $pemesanan->id,
                        'layanan_id' => $it->item_id,
                        'jumlah' => $it->jumlah ?? 1,
                        'total_harga' => $it->total_harga,
                    ]);
                }
            }

            invoice::create([
                'invoice_number' => strtoupper(uniqid('INV')),
                'petugas_id' => null,
                'order_id' => null,
                'pemesanan_id' => $pemesanan->id,
                'user_id' => $user->id,
                'total_amount' => $total,
                'jumlah_bayar' => 0,
                'status_pembayaran' => 'pending',
            ]);

            // delete processed cart items
            CartItem::whereIn('id', $items->pluck('id')->toArray())->delete();

            DB::commit();

            $pemesanan->load('bookingKamars.kamar', 'pesananLayanans.layanan', 'invoice');
            return response()->json(['success' => true, 'data' => $pemesanan]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
