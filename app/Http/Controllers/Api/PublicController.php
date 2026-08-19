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
        $query = Product::where('is_active', true)->with('prices');

        if ($request->filled('category')) {
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

    public function productDetail(Product $product)
    {
        if (!$product->is_active) {
            return response()->json(['message' => 'Produk tidak ditemukan'], 404);
        }

        $product->display_price = $product->priceForLevel('reseller');

        return response()->json($product);
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
