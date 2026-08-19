<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BuybackController;
use App\Http\Controllers\Api\CommissionController;
use App\Http\Controllers\Api\DeliveryOrderController;
use App\Http\Controllers\Api\DeliveryTrackingController;
use App\Http\Controllers\Api\DuitkuController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\MemberCardController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\OutletController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\PromoController;
use App\Http\Controllers\Api\RegionController;
use App\Http\Controllers\Api\ReturnItemController;
use App\Http\Controllers\Api\StockController;
use App\Http\Controllers\Api\StockMutationController;
use App\Http\Controllers\Api\VisitController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\WhatsappNotificationController;
use Illuminate\Support\Facades\Route;

// --- Auth ---
Route::post('/auth/login', [AuthController::class, 'login']);

// --- Tracking publik (tanpa login, dipakai outlet untuk cek status kirim) ---
Route::get('/track/{doNumber}', [DeliveryTrackingController::class, 'publicTrack']);

// --- Duitku callback (tanpa auth, verifikasi via signature) ---
Route::post('/payment/duitku/callback', [DuitkuController::class, 'callback']);
Route::get('/payment/duitku/return', [DuitkuController::class, 'returnUrl']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // --- Master Data ---
    Route::apiResource('products', ProductController::class);
    Route::apiResource('outlets', OutletController::class);
    Route::apiResource('regions', RegionController::class);

    // --- Canvasing ---
    Route::post('/visits/checkin', [VisitController::class, 'checkin']);
    Route::apiResource('visits', VisitController::class)->except(['store']);

    // --- Order ---
    Route::apiResource('orders', OrderController::class);
    Route::post('/orders/{order}/approve', [OrderController::class, 'approve']);
    Route::post('/orders/{order}/complete', [OrderController::class, 'markCompleted']);

    // --- Inventory ---
    Route::apiResource('stocks', StockController::class);
    Route::get('/stock-mutations', [StockMutationController::class, 'index']);
    Route::post('/stock-mutations', [StockMutationController::class, 'store']);

    // --- Pengiriman & Tracking ---
    Route::apiResource('delivery-orders', DeliveryOrderController::class);
    Route::post('/delivery-orders/{deliveryOrder}/track', [DeliveryTrackingController::class, 'store']);
    Route::post('/delivery-orders/{deliveryOrder}/pod', [DeliveryOrderController::class, 'uploadPod']);

    // --- Piutang & Retur ---
    Route::apiResource('invoices', InvoiceController::class);
    Route::apiResource('returns', ReturnItemController::class);

    // --- Promo & Reward ---
    Route::apiResource('promos', PromoController::class);

    // --- Komisi Jaringan ---
    Route::get('/commissions', [CommissionController::class, 'index']);
    Route::post('/commissions/{commission}/payout', [CommissionController::class, 'payout']);

    // --- Saldo / Wallet ---
    Route::get('/wallet', [WalletController::class, 'show']);
    Route::post('/wallet/topup', [WalletController::class, 'topup']);
    Route::get('/wallet/mutations', [WalletController::class, 'mutations']);

    // --- Cashback Barang Bekas ---
    Route::apiResource('buyback', BuybackController::class);

    // --- Member Card ---
    Route::get('/member-card', [MemberCardController::class, 'show']);

    // --- Notifikasi WA ---
    Route::post('/notifications/whatsapp/test', [WhatsappNotificationController::class, 'test']);

    // --- Payment Gateway Duitku (create transaction butuh auth) ---
    Route::post('/payment/duitku/create', [DuitkuController::class, 'createTransaction']);
});
