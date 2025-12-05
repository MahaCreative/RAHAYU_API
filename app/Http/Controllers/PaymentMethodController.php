<?php

namespace App\Http\Controllers;

use App\Models\Pemesanan;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    // Return payment methods and pemesanan summary
    public function showPaymentMethods($pemesanan_id)
    {
        $p = Pemesanan::with('bookingKamars', 'pesananLayanans')->find($pemesanan_id);
        if (! $p) return response()->json(['success' => false, 'message' => 'Pemesanan not found'], 404);

        $methods = [
            'bank_transfer' => [
                'label' => 'Transfer Bank',
                'options' => [
                    ['code' => 'bri', 'name' => 'BRI VA'],
                    ['code' => 'bca', 'name' => 'BCA VA'],
                    ['code' => 'mandiri', 'name' => 'Mandiri VA'],
                    ['code' => 'bni', 'name' => 'BNI VA'],
                ],
            ],
            'ewallet' => [
                'label' => 'E-Wallet',
                'options' => [
                    ['code' => 'gopay', 'name' => 'Gopay'],
                    ['code' => 'dana', 'name' => 'Dana'],
                    ['code' => 'shopeepay', 'name' => 'Shopeepay'],
                ],
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'pemesanan' => $p,
                'methods' => $methods,
            ],
        ]);
    }
}
