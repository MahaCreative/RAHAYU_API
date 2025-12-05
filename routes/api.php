<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Costumer\CartController;
use App\Http\Controllers\Costumer\KamarController as CostumerKamarController;
use App\Http\Controllers\Costumer\LayananController as CostumerLayananController;
use App\Http\Controllers\Costumer\TipeKamarController as CostumerTipeKamarController;
use App\Http\Controllers\Costumer\TipeLayananController as CostumerTipeLayananController;
use App\Http\Controllers\KamarController;
use App\Http\Controllers\LayananController;
use App\Http\Controllers\ProfileHotelController;
use App\Http\Controllers\TipeKamarController;
use App\Http\Controllers\TipeLayananController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

//authenticated routes
Route::get('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('reset-password', [AuthController::class, 'resetPassword']);
Route::post('verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('resend-otp', [AuthController::class, 'resendOtp']);
Route::get('profile', [AuthController::class, 'profile'])->middleware('auth:sanctum');
Route::post('update-profile', [AuthController::class, 'updateProfile'])->middleware('auth:sanctum');  // untuk edit atau buat profile selain email, telephone, password
Route::post('change-account', [AuthController::class, 'changePassword'])->middleware('auth:sanctum'); // route untuk update informasi account (email, telephone, password)
Route::post('upload-photo', [AuthController::class, 'uploadPhoto'])->middleware('auth:sanctum'); // untuk digunakan upload foto / ganti profile
Route::get('check-auth', [AuthController::class, 'checkAuth'])->middleware('auth:sanctum');

// Role Global Data
// Route::resource('tipe-kamar', TipeKamarController::class)->only(['index', 'show']);
// Route::resource('tipe-layanan', TipeLayananController::class)->only(['index', 'show']);
// Route::resource('kamar', KamarController::class)->only(['index', 'show']);
// Route::resource('layanan', LayananController::class)->only(['index', 'show']);
// Route::get('profile-hotel', [ProfileHotelController::class, 'index']);

Route::get('user', [UserController::class, 'index']);
Route::get('block-user/{id}', [UserController::class, 'blockUser']);
Route::get('unblock-user/{id}', [UserController::class, 'unblockUser']);
Route::get('user/{id}', [UserController::class, 'show']);

// Route::middleware('apiauth')->group(function () {
// Role Admin Data Management

Route::resource('tipe-kamar', TipeKamarController::class);
Route::resource('tipe-layanan', TipeLayananController::class);
Route::resource('kamar', KamarController::class);
Route::resource('layanan', LayananController::class);
Route::put('profile-hotel', [ProfileHotelController::class, 'update']);
// });

//Route untuk Costumer
Route::get('get-tipe-kamar', [CostumerTipeKamarController::class, 'index']);
Route::get('show-tipe-kamar/{id}', [CostumerTipeKamarController::class, 'show']);
Route::get('get-tipe-layanan', [CostumerTipeLayananController::class, 'index']);
Route::get('show-tipe-layanan/{id}', [CostumerTipeLayananController::class, 'show']);
Route::get('get-kamar', [CostumerKamarController::class, 'index']);
Route::get('show-kamar/{id}', [CostumerKamarController::class, 'show']);
Route::get('get-layanan', [CostumerLayananController::class, 'index']);
Route::get('show-layanan/{id}', [CostumerLayananController::class, 'show']);

// Pemesanan (history) for customers
Route::middleware('auth:sanctum')->prefix('pemesanan')->group(function () {
    Route::get('/', [\App\Http\Controllers\Costumer\PemesananController::class, 'index']);
    Route::get('{pemesanan}', [\App\Http\Controllers\Costumer\PemesananController::class, 'show']);
    Route::post('/', [\App\Http\Controllers\Costumer\PemesananController::class, 'store']);
    Route::get('{pemesanan}/invoice', [\App\Http\Controllers\InvoiceController::class, 'show']);
    Route::get('{pemesanan}/invoice/pdf', [\App\Http\Controllers\InvoiceController::class, 'pdf']);
});

// BookingKamar QR endpoint for customer
Route::middleware('auth:sanctum')->get('booking-kamar/{id}/qr', [\App\Http\Controllers\BookingKamarController::class, 'qr']);

// Admin pemesanan & payment management
Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
    Route::get('pemesanan', [\App\Http\Controllers\Admin\PemesananController::class, 'index']);
    Route::get('pemesanan/{id}', [\App\Http\Controllers\Admin\PemesananController::class, 'show']);
    Route::get('pemesanan-export', [\App\Http\Controllers\Admin\PemesananController::class, 'export']);

    // Reports
    Route::get('reports/booking', [\App\Http\Controllers\Admin\ReportController::class, 'bookingReport']);
    Route::get('reports/tamu', [\App\Http\Controllers\Admin\ReportController::class, 'tamuReport']);
    Route::get('reports/finance', [\App\Http\Controllers\Admin\ReportController::class, 'financeReport']);

    // Dashboard summary
    Route::get('dashboard/summary', [\App\Http\Controllers\Admin\DashboardController::class, 'summary']);

    // Admin checkin / checkout for booking_kamar
    Route::post('booking-kamar/{id}/confirm-checkin', [\App\Http\Controllers\Admin\BookingKamarController::class, 'confirmCheckin']);
    Route::post('booking-kamar/{id}/confirm-checkout', [\App\Http\Controllers\Admin\BookingKamarController::class, 'confirmCheckout']);

    Route::get('payments', [\App\Http\Controllers\Admin\PaymentController::class, 'index']);
    Route::get('payments/{id}', [\App\Http\Controllers\Admin\PaymentController::class, 'show']);
});

// Cart routes for customers (requires auth)
Route::middleware('auth:sanctum')->prefix('cart')->group(function () {
    Route::get('/', [\App\Http\Controllers\Costumer\CartController::class, 'getCart']);
    Route::post('add', [\App\Http\Controllers\Costumer\CartController::class, 'addToCart']);
    Route::put('check/{id}', [\App\Http\Controllers\Costumer\CartController::class, 'updateCheck']);
    Route::put('jumlah/{id}', [\App\Http\Controllers\Costumer\CartController::class, 'updateJumlah']);
    Route::put('update/{id}', [\App\Http\Controllers\Costumer\CartController::class, 'updateItem']);
    Route::delete('{id}', [\App\Http\Controllers\Costumer\CartController::class, 'removeItem']);
});

// Checkout selected cart items
Route::middleware('auth:sanctum')->post('checkout/selected', [\App\Http\Controllers\CheckoutController::class, 'checkoutSelected']);

// New checkout route: create pemesanan from cart
Route::middleware('auth:sanctum')->post('checkout', [\App\Http\Controllers\CheckoutController::class, 'processCheckout']);

// Payment method and payment endpoints
Route::get('payment-method/{pemesanan}', [\App\Http\Controllers\PaymentMethodController::class, 'showPaymentMethods']);
Route::post('payment/create-va', [\App\Http\Controllers\PaymentController::class, 'createVirtualAccount']);
Route::post('payment/create-ewallet', [\App\Http\Controllers\PaymentController::class, 'createEwallet']);
Route::get('payment/{pemesanan}', [\App\Http\Controllers\PaymentController::class, 'showPaymentDetail']);
Route::get('payment/status/{pemesanan}', [\App\Http\Controllers\PaymentStatusController::class, 'check']);
Route::post('payment/cancel/{orderId}', [\App\Http\Controllers\PaymentController::class, 'cancelPayment']);

// Order cancellation
Route::middleware('auth:sanctum')->post('order/cancel/{pemesanan}', [\App\Http\Controllers\CancelOrderController::class, 'cancel']);

// Midtrans callback (public) - no auth required
Route::post('midtrans/callback', [\App\Http\Controllers\PaymentController::class, 'handleCallback']);
