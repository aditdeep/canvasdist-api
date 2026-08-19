<?php

use Illuminate\Support\Facades\Route;

// --- Auth ---
Route::post('/auth/login', 'AuthController@login');
Route::post('/auth/logout', 'AuthController@logout')->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    // --- Master Data ---
    Route::apiResource('products', 'Api\ProductController');
    Route::apiResource('outlets', 'Api\OutletController');
    Route::apiResource('regions', 'Api\RegionController');

    // --- Canvasing ---
    Route::post('/visits/checkin', 'Api\VisitController@checkin');
    Route::apiResource('visits', 'Api\VisitController')->except(['store']);

    // --- Order ---
    Route::apiResource('orders', 'Api\OrderController');
    Route::post('/orders/{order}/approve', 'Api\OrderController@approve');

    // --- Inventory ---
    Route::apiResource('stocks', 'Api\StockController');
    Route::post('/stock-mutations', 'Api\StockMutationController@store');

    // --- Pengiriman & Tracking ---
    Route::apiResource('delivery-orders', 'Api\DeliveryOrderController');
    Route::post('/delivery-orders/{deliveryOrder}/track', 'Api\DeliveryTrackingController@store');
    Route::post('/delivery-orders/{deliveryOrder}/pod', 'Api\DeliveryOrderController@uploadPod');
    Route::get('/track/{doNumber}', 'Api\DeliveryTrackingController@publicTrack'); // tracking publik tanpa login

    // --- Piutang & Retur ---
    Route::apiResource('invoices', 'Api\InvoiceController');
    Route::apiResource('returns', 'Api\ReturnItemController');

    // --- Promo & Reward ---
    Route::apiResource('promos', 'Api\PromoController');

    // --- Komisi Jaringan ---
    Route::get('/commissions', 'Api\CommissionController@index');
    Route::post('/commissions/{commission}/payout', 'Api\CommissionController@payout');

    // --- Saldo / Wallet ---
    Route::get('/wallet', 'Api\WalletController@show');
    Route::post('/wallet/topup', 'Api\WalletController@topup');
    Route::get('/wallet/mutations', 'Api\WalletController@mutations');

    // --- Cashback Barang Bekas ---
    Route::apiResource('buyback', 'Api\BuybackController');

    // --- Member Card ---
    Route::get('/member-card', 'Api\MemberCardController@show');

    // --- Notifikasi WA ---
    Route::post('/notifications/whatsapp/test', 'Api\WhatsappNotificationController@test');
});

// --- Payment Gateway Duitku (callback tidak pakai auth, verifikasi via signature) ---
Route::post('/payment/duitku/create', 'Api\DuitkuController@createTransaction')->middleware('auth:sanctum');
Route::post('/payment/duitku/callback', 'Api\DuitkuController@callback');
Route::get('/payment/duitku/return', 'Api\DuitkuController@returnUrl');
