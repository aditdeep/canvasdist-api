<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BannerController;
use App\Http\Controllers\Api\BuybackController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CommissionController;
use App\Http\Controllers\Api\DeliveryOrderController;
use App\Http\Controllers\Api\DeliveryLegController;
use App\Http\Controllers\Api\DeliveryTrackingController;
use App\Http\Controllers\Api\DuitkuController;
use App\Http\Controllers\Api\HubController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\MemberCardController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\OutletController;
use App\Http\Controllers\Api\PaymentTransactionController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\PromoController;
use App\Http\Controllers\Api\PublicController;
use App\Http\Controllers\Api\RegionController;
use App\Http\Controllers\Api\ReturnItemController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\StockController;
use App\Http\Controllers\Api\StockMutationController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\VisitController;
use App\Http\Controllers\Api\WarehouseController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\WhatsappNotificationController;
use App\Http\Controllers\Api\WithdrawalController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Ringkasan Role
|--------------------------------------------------------------------------
| super_admin : akses penuh semua endpoint
| wilayah     : kelola region, approve order besar, lihat laporan jaringan
| agen        : kelola outlet/gudang/sales miliknya, approve order, buat DO
| reseller    : lihat & buat order sendiri, saldo sendiri
| sales       : checkin canvasing, buat order, input buyback
| gudang      : kelola stok, buat Surat Jalan (DO)
| kurir       : update status pengiriman & tracking yang di-assign ke dia
|
| Endpoint baca (index/show) umumnya terbuka untuk semua role yang login,
| lalu di-scope per user di controller (lihat catatan "scoped" di masing-
| masing controller). Endpoint tulis (store/update/destroy) yang berdampak
| ke data lintas-user dibatasi via middleware `role:`.
*/

// --- Auth ---
Route::post('/auth/login', [AuthController::class, 'login']);

// --- Storefront publik (tanpa login) ---
Route::get('/public/products', [PublicController::class, 'products']);
Route::get('/public/products/{product}', [PublicController::class, 'productDetail']);
Route::get('/public/regions', [PublicController::class, 'regions']);
Route::post('/public/register', [PublicController::class, 'registerCustomer']);
Route::get('/public/settings', [SettingsController::class, 'public']);
Route::get('/public/categories', [PublicController::class, 'categories']);
Route::get('/public/banners', [PublicController::class, 'banners']);

// --- Tracking publik (tanpa login, dipakai outlet untuk cek status kirim) ---
Route::get('/track/{doNumber}', [DeliveryTrackingController::class, 'publicTrack']);

// --- Duitku callback (tanpa auth, verifikasi via signature) ---
Route::post('/payment/duitku/callback', [DuitkuController::class, 'callback']);
Route::get('/payment/duitku/return', [DuitkuController::class, 'returnUrl']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/profile', [AuthController::class, 'updateProfile']);
    Route::post('/auth/change-password', [AuthController::class, 'changePassword']);
    Route::post('/auth/push-token', [AuthController::class, 'registerPushToken']);

    // --- Master Data ---
    Route::apiResource('products', ProductController::class)->only(['index', 'show']);
    Route::apiResource('products', ProductController::class)
        ->only(['store', 'update', 'destroy'])
        ->middleware('role:super_admin,wilayah,agen');

    Route::apiResource('categories', CategoryController::class)->only(['index', 'show']);
    Route::apiResource('categories', CategoryController::class)
        ->only(['store', 'update', 'destroy'])
        ->middleware('role:super_admin,wilayah,agen');

    Route::apiResource('banners', BannerController::class)->only(['index', 'show']);
    Route::apiResource('banners', BannerController::class)
        ->only(['store', 'update', 'destroy'])
        ->middleware('role:super_admin');

    Route::apiResource('outlets', OutletController::class)->only(['index', 'show']);
    Route::apiResource('outlets', OutletController::class)
        ->only(['store', 'update', 'destroy'])
        ->middleware('role:super_admin,wilayah,agen,sales');

    Route::apiResource('regions', RegionController::class)->only(['index', 'show']);
    Route::apiResource('regions', RegionController::class)
        ->only(['store', 'update', 'destroy'])
        ->middleware('role:super_admin,wilayah');

    Route::apiResource('users', UserController::class)->middleware('role:super_admin');

    // --- Canvasing ---
    Route::post('/visits/checkin', [VisitController::class, 'checkin'])->middleware('role:sales,agen,super_admin');
    Route::apiResource('visits', VisitController::class)->except(['store']);

    // --- Order ---
    Route::apiResource('orders', OrderController::class);
    Route::post('/orders/{order}/approve', [OrderController::class, 'approve'])
        ->middleware('role:super_admin,wilayah,agen');
    Route::post('/orders/{order}/complete', [OrderController::class, 'markCompleted'])
        ->middleware('role:super_admin,wilayah,agen,gudang,kurir');

    // --- Inventory ---
    Route::apiResource('warehouses', WarehouseController::class)->only(['index', 'show']);
    Route::apiResource('warehouses', WarehouseController::class)
        ->only(['store', 'update', 'destroy'])
        ->middleware('role:super_admin,wilayah,agen');

    Route::apiResource('stocks', StockController::class)->only(['index', 'show']);
    Route::apiResource('stocks', StockController::class)
        ->only(['store', 'update', 'destroy'])
        ->middleware('role:super_admin,wilayah,agen,gudang');

    Route::get('/stock-mutations', [StockMutationController::class, 'index']);
    Route::post('/stock-mutations', [StockMutationController::class, 'store'])
        ->middleware('role:super_admin,wilayah,agen,gudang');

    // --- Pengiriman & Tracking ---
    Route::apiResource('delivery-orders', DeliveryOrderController::class)->only(['index', 'show']);
    Route::apiResource('delivery-orders', DeliveryOrderController::class)
        ->only(['store'])
        ->middleware('role:super_admin,wilayah,agen,gudang');
    Route::apiResource('delivery-orders', DeliveryOrderController::class)
        ->only(['update', 'destroy'])
        ->middleware('role:super_admin,agen,gudang,kurir');

    Route::post('/delivery-orders/{deliveryOrder}/track', [DeliveryTrackingController::class, 'store'])
        ->middleware('role:super_admin,agen,gudang,kurir');
    Route::post('/delivery-orders/{deliveryOrder}/pod', [DeliveryOrderController::class, 'uploadPod'])
        ->middleware('role:super_admin,agen,gudang,kurir');

    // --- Rute Multi-Hub ---
    Route::get('/hubs', [HubController::class, 'index']);
    Route::apiResource('hubs', HubController::class)->only(['store', 'update', 'destroy'])
        ->middleware('role:super_admin,wilayah,agen,gudang');
    Route::get('/delivery-orders/{deliveryOrder}/legs', [DeliveryLegController::class, 'index']);
    Route::post('/delivery-orders/{deliveryOrder}/legs', [DeliveryLegController::class, 'store'])
        ->middleware('role:super_admin,wilayah,agen,gudang');
    Route::post('/delivery-legs/{leg}/start', [DeliveryLegController::class, 'start'])
        ->middleware('role:super_admin,agen,gudang,kurir');
    Route::post('/delivery-legs/{leg}/arrive', [DeliveryLegController::class, 'arrive'])
        ->middleware('role:super_admin,agen,gudang,kurir');

    // --- Piutang & Retur ---
    Route::apiResource('invoices', InvoiceController::class)->only(['index', 'show']);
    Route::apiResource('invoices', InvoiceController::class)
        ->only(['store', 'update', 'destroy'])
        ->middleware('role:super_admin,wilayah,agen');

    Route::apiResource('returns', ReturnItemController::class)->only(['index', 'show', 'store']);
    Route::apiResource('returns', ReturnItemController::class)
        ->only(['update', 'destroy'])
        ->middleware('role:super_admin,wilayah,agen,gudang');

    // --- Promo & Reward ---
    Route::apiResource('promos', PromoController::class)->only(['index', 'show']);
    Route::apiResource('promos', PromoController::class)
        ->only(['store', 'update', 'destroy'])
        ->middleware('role:super_admin,wilayah,agen');

    // --- Komisi Jaringan ---
    Route::get('/commissions', [CommissionController::class, 'index']);
    Route::post('/commissions/{commission}/payout', [CommissionController::class, 'payout'])
        ->middleware('role:super_admin,wilayah,agen');

    // --- Saldo / Wallet (selalu ter-scope ke user login sendiri, semua role boleh) ---
    Route::get('/wallet', [WalletController::class, 'show']);
    Route::get('/wallet/payment-methods', [WalletController::class, 'paymentMethods']);
    Route::post('/wallet/topup', [WalletController::class, 'topup']);
    Route::get('/wallet/mutations', [WalletController::class, 'mutations']);

    // --- Withdraw (tarik saldo ke rekening bank) ---
    Route::get('/withdrawals', [WithdrawalController::class, 'index']);
    Route::post('/withdrawals', [WithdrawalController::class, 'store']);
    Route::get('/withdrawals/{withdrawal}', [WithdrawalController::class, 'show']);
    Route::put('/withdrawals/{withdrawal}', [WithdrawalController::class, 'update'])
        ->middleware('role:super_admin,wilayah,agen');

    // --- Cashback Barang Bekas ---
    Route::apiResource('buyback', BuybackController::class)->only(['index', 'show', 'store']);
    Route::apiResource('buyback', BuybackController::class)
        ->only(['update', 'destroy'])
        ->middleware('role:super_admin,wilayah,agen,gudang');

    // --- Member Card ---
    Route::get('/member-card', [MemberCardController::class, 'show']);

    // --- Notifikasi WA ---
    Route::post('/notifications/whatsapp/test', [WhatsappNotificationController::class, 'test'])
        ->middleware('role:super_admin');

    // --- Pengaturan Aplikasi (branding, fee platform) ---
    Route::get('/settings', [SettingsController::class, 'show'])->middleware('role:super_admin');
    Route::post('/settings', [SettingsController::class, 'update'])->middleware('role:super_admin');

    // --- Payment Gateway Duitku ---
    Route::get('/payment/transactions', [PaymentTransactionController::class, 'index']);
    Route::post('/payment/duitku/create', [DuitkuController::class, 'createTransaction']);
});
