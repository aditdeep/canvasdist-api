<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\Region;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * Endpoint publik (tanpa auth) untuk storefront: browsing produk & wilayah
 * bisa diakses siapa saja, tapi checkout/beli tetap wajib login (lihat
 * OrderController — customer harus punya token Sanctum untuk buat order).
 */
class PublicController extends Controller
{
    /**
     * Katalog produk untuk storefront. Harga yang ditampilkan pakai level
     * 'reseller' (harga retail-facing), bukan harga internal agen/wilayah.
     */
    public function products(Request $request)
    {
        $query = Product::where('is_active', true)->with('prices', 'categoryModel');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        } elseif ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('search')) {
            $query->where('name', 'ilike', '%' . $request->search . '%');
        }

        $products = $query->paginate(20);

        $products->getCollection()->transform(function ($product) {
            $product->display_price = $product->priceForLevel('reseller');
            return $product;
        });

        return response()->json($products);
    }

    public function productDetail(Request $request, Product $product)
    {
        if (!$product->is_active) {
            return response()->json(['message' => 'Produk tidak ditemukan'], 404);
        }

        $basePrice = (float) $product->priceForLevel('reseller');
        $promo = \App\Models\Promo::where('is_active', true)
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->where(function ($q) {
                $q->whereNull('target_level')->orWhere('target_level', 'reseller');
            })
            ->orderByDesc('value')
            ->first();

        $discountedPrice = $basePrice;
        $promoLabel = null;

        if ($promo) {
            if ($promo->type === 'discount_percent') {
                $discountedPrice = $basePrice - ($basePrice * ((float) $promo->value / 100));
                $promoLabel = $promo->name . ' -' . rtrim(rtrim(number_format((float) $promo->value, 1), '0'), '.') . '%';
            } elseif (in_array($promo->type, ['discount_fixed', 'tiered'])) {
                $discountedPrice = max(0, $basePrice - (float) $promo->value);
                $promoLabel = $promo->name;
            }
        }

        $product->display_price = $basePrice;
        $product->discounted_price = round($discountedPrice, 2);
        $product->promo_label = $promoLabel;

        // Ongkir: kalau user login sebagai customer & sudah punya agen, pakai
        // shipping_fee milik agen itu. Route ini publik (tanpa middleware
        // auth:sanctum) jadi resolve user manual dari token kalau ada, tanpa
        // memaksa route ini wajib login.
        $shippingFee = null;
        $user = auth('sanctum')->user();
        if ($user && $user->role === 'customer' && $user->outlet?->agent) {
            $shippingFee = (float) $user->outlet->agent->shipping_fee;
        }
        $product->shipping_fee = $shippingFee;

        $related = Product::where('is_active', true)
            ->where('id', '!=', $product->id)
            ->when($product->category, fn ($q) => $q->where('category', $product->category))
            ->limit(4)
            ->get()
            ->map(function ($p) {
                $p->display_price = $p->priceForLevel('reseller');
                return $p;
            });

        return response()->json(['product' => $product, 'related' => $related]);
    }

    /**
     * Daftar kategori aktif (dengan gambar) untuk ditampilkan di storefront.
     */
    public function categories()
    {
        return response()->json(
            \App\Models\Category::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get()
        );
    }

    /**
     * Banner hero slider aktif untuk storefront.
     */
    public function banners()
    {
        return response()->json(
            \App\Models\Banner::where('is_active', true)->orderBy('sort_order')->get()
        );
    }

    /**
     * Daftar wilayah untuk dipilih customer sebelum browsing (menentukan
     * agen mana yang otomatis melayani mereka).
     */
    public function regions()
    {
        return response()->json(Region::orderBy('name')->get());
    }

    /**
     * Registrasi customer baru untuk storefront. Otomatis:
     * 1. Cari agen aktif dengan region_code yang cocok dengan wilayah customer
     * 2. Buat 1 Outlet baru merepresentasikan alamat customer, di-assign ke agen itu
     * 3. Link user customer ke outlet itu
     *
     * Kalau tidak ada agen yang cocok di wilayah tsb, order tetap bisa dibuat
     * tapi agent_id kosong — perlu di-assign manual oleh admin.
     */
    public function registerCustomer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string',
            'password' => 'required|string|min:6',
            'region_code' => 'required|string|exists:regions,code',
            'address' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        $matchedAgent = User::where('role', 'agen')
            ->where('region_code', $request->region_code)
            ->where('is_active', true)
            ->first();

        $outlet = Outlet::create([
            'name' => 'Alamat ' . $request->name,
            'owner_name' => $request->name,
            'phone' => $request->phone,
            'address' => $request->address,
            'agent_id' => $matchedAgent?->id,
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => 'customer',
            'region_code' => $request->region_code,
            'address' => $request->address,
            'outlet_id' => $outlet->id,
            'is_active' => true,
        ]);

        Wallet::create(['user_id' => $user->id, 'balance' => 0]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
            'agent_assigned' => $matchedAgent !== null,
        ], 201);
    }
}
