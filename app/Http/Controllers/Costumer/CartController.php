<?php

namespace App\Http\Controllers\Costumer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Kamar;
use App\Models\Layanan;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class CartController extends Controller
{
    public function getCart(Request $request)
    {
        $user = $request->user();

        $cart = Cart::firstOrCreate(
            ['user_id' => $user->id, 'is_checked_out' => false],
            ['user_id' => $user->id, 'is_checked_out' => false]
        );

        $cart->load(['items' => function ($q) {
            $q->with('item');
        }]);

        // Transform items for a clean JSON
        $items = $cart->items->map(function ($ci) {
            $item = $ci->item;
            $typeName = $ci->item_type_name ?? null;

            $detail = [
                'cart_item_id' => $ci->id,
                'item_id' => $ci->item_id,
                'item_type' => $typeName,
                'jumlah' => $ci->jumlah,
                'harga_satuan' => $ci->harga_satuan,
                'total_harga' => $ci->total_harga,
                'tanggal_checkin' => $ci->tanggal_checkin,
                'tanggal_checkout' => $ci->tanggal_checkout,
                'checked' => (bool) $ci->checked,
                'meta' => null,
            ];

            if ($item) {
                if ($typeName === 'kamar') {
                    $detail['meta'] = [
                        'nama' => $item->nomor_kamar ?? ($item->tipeKamar->nama ?? null),
                        'harga' => $item->harga_kamar ?? null,
                        'foto' => $item->foto_kamar ?? null,
                    ];
                } else {
                    $detail['meta'] = [
                        'nama' => $item->nama_layanan ?? null,
                        'harga' => $item->harga_layanan ?? null,
                        'foto' => $item->foto_layanan ?? null,
                    ];
                }
            }

            return $detail;
        });

        return response()->json([
            'success' => true,
            'data' => [
                'cart_id' => $cart->id,
                'is_checked_out' => (bool) $cart->is_checked_out,
                'items' => $items,
            ],
        ]);
    }

    public function addToCart(Request $request)
    {
        $user = $request->user();

        $v = Validator::make($request->all(), [
            'item_id' => 'required|integer',
            'item_type' => 'required|string|in:kamar,layanan',
            'jumlah' => 'required|integer|min:1',
            'tanggal_checkin' => 'nullable|date',
            'tanggal_checkout' => 'nullable|date',
        ]);

        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }

        $map = [
            'kamar' => Kamar::class,
            'layanan' => Layanan::class,
        ];

        $modelClass = $map[$request->input('item_type')];
        $item = $modelClass::find($request->input('item_id'));

        if (! $item) {
            return response()->json(['success' => false, 'message' => 'Item not found'], 404);
        }


        // determine price per unit (for kamar it's per night)
        $harga = $request->input('item_type') === 'kamar' ? ($item->harga_kamar ?? 0) : ($item->harga_layanan ?? 0);

        // If kamar, we accept checkin/checkout and compute nights
        $tanggalCheckin = $request->input('tanggal_checkin');
        $tanggalCheckout = $request->input('tanggal_checkout');
        $nights = 1;
        if ($request->input('item_type') === 'kamar') {
            if (! $tanggalCheckin || ! $tanggalCheckout) {
                return response()->json(['success' => false, 'message' => 'tanggal_checkin and tanggal_checkout are required for kamar'], 422);
            }
            try {
                $d1 = Carbon::parse($tanggalCheckin)->startOfDay();
                $d2 = Carbon::parse($tanggalCheckout)->startOfDay();
            } catch (\Exception $e) {
                return response()->json(['success' => false, 'message' => 'Invalid date format'], 422);
            }
            if ($d2->lte($d1)) {
                return response()->json(['success' => false, 'message' => 'tanggal_checkout must be after tanggal_checkin'], 422);
            }
            $nights = $d1->diffInDays($d2);
        }

        $requested = max(1, (int) $request->input('jumlah'));

        $cart = Cart::firstOrCreate(['user_id' => $user->id, 'is_checked_out' => false]);

        // Create one CartItem per room/service (1 per entry). jumlah is fixed to 1.
        $created = [];
        for ($i = 0; $i < $requested; $i++) {
            $total = $harga * $nights;
            $ci = CartItem::create([
                'cart_id' => $cart->id,
                'item_id' => $item->id,
                'item_type' => $modelClass,
                'jumlah' => 1,
                'harga_satuan' => $harga,
                'total_harga' => $total,
                'tanggal_checkin' => $tanggalCheckin,
                'tanggal_checkout' => $tanggalCheckout,
                'checked' => true,
            ]);
            $created[] = $ci->load('item');
        }

        return response()->json(['success' => true, 'data' => $created]);
    }

    public function updateCheck($id)
    {
        $user = auth()->user();
        $ci = CartItem::find($id);
        if (! $ci) return response()->json(['success' => false, 'message' => 'Not found'], 404);

        $cart = $ci->cart;
        if (! $cart || $cart->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $ci->checked = ! (bool) $ci->checked;
        $ci->save();

        return response()->json(['success' => true, 'data' => $ci]);
    }

    public function updateJumlah(Request $request, $id)
    {
        // Per-item quantity not supported. Each cart item represents a single booking (jumlah = 1).
        return response()->json([
            'success' => false,
            'message' => 'Per-item quantity not supported. Add items separately (1 per cart entry).'
        ], 422);
    }

    /**
     * Update cart item (e.g., change checkin/checkout dates)
     */
    public function updateItem(Request $request, $id)
    {
        $ci = CartItem::find($id);
        if (! $ci) return response()->json(['success' => false, 'message' => 'Not found'], 404);

        $cart = $ci->cart;
        $user = $request->user();
        if (! $cart || $cart->user_id !== $user->id) return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);

        $v = Validator::make($request->all(), [
            'tanggal_checkin' => 'nullable|date',
            'tanggal_checkout' => 'nullable|date',
        ]);
        if ($v->fails()) return response()->json(['success' => false, 'errors' => $v->errors()], 422);

        // Only applicable for kamar items
        $typeName = strtolower(class_basename($ci->item_type));
        if ($typeName !== 'kamar') {
            return response()->json(['success' => false, 'message' => 'Update dates only supported for kamar items'], 422);
        }

        $tanggalCheckin = $request->input('tanggal_checkin');
        $tanggalCheckout = $request->input('tanggal_checkout');
        if (! $tanggalCheckin || ! $tanggalCheckout) {
            return response()->json(['success' => false, 'message' => 'tanggal_checkin and tanggal_checkout are required'], 422);
        }

        try {
            $d1 = Carbon::parse($tanggalCheckin)->startOfDay();
            $d2 = Carbon::parse($tanggalCheckout)->startOfDay();
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Invalid date format'], 422);
        }
        if ($d2->lte($d1)) {
            return response()->json(['success' => false, 'message' => 'tanggal_checkout must be after tanggal_checkin'], 422);
        }

        $nights = $d1->diffInDays($d2);
        $ci->tanggal_checkin = $tanggalCheckin;
        $ci->tanggal_checkout = $tanggalCheckout;
        $ci->total_harga = ($ci->harga_satuan ?? 0) * $nights;
        $ci->save();

        return response()->json(['success' => true, 'data' => $ci]);
    }

    public function removeItem($id)
    {
        $user = auth()->user();
        $ci = CartItem::find($id);
        if (! $ci) return response()->json(['success' => false, 'message' => 'Not found'], 404);

        $cart = $ci->cart;
        if (! $cart || $cart->user_id !== $user->id) return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);

        $ci->delete();
        return response()->json(['success' => true, 'message' => 'Deleted']);
    }
}
