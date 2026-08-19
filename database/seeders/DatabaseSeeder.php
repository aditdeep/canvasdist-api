<?php

namespace Database\Seeders;

use App\Models\Outlet;
use App\Models\Product;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Super Admin
        $admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@canvasdist.test',
            'phone' => '081200000000',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        // Hierarki: Wilayah -> Agen -> Reseller
        $wilayah = User::create([
            'name' => 'Wilayah Jateng',
            'email' => 'wilayah@canvasdist.test',
            'password' => Hash::make('password'),
            'role' => 'wilayah',
            'is_active' => true,
        ]);

        $agen = User::create([
            'name' => 'Agen Semarang',
            'email' => 'agen@canvasdist.test',
            'password' => Hash::make('password'),
            'role' => 'agen',
            'parent_id' => $wilayah->id,
            'is_active' => true,
        ]);

        $reseller = User::create([
            'name' => 'Reseller Toko Barokah',
            'email' => 'reseller@canvasdist.test',
            'password' => Hash::make('password'),
            'role' => 'reseller',
            'parent_id' => $agen->id,
            'is_active' => true,
        ]);

        $sales = User::create([
            'name' => 'Sales Budi',
            'email' => 'sales@canvasdist.test',
            'password' => Hash::make('password'),
            'role' => 'sales',
            'parent_id' => $agen->id,
            'is_active' => true,
        ]);

        // Buat wallet untuk semua user
        foreach ([$admin, $wilayah, $agen, $reseller, $sales] as $user) {
            Wallet::create(['user_id' => $user->id, 'balance' => 0]);
        }

        // Gudang & produk contoh
        $warehouse = Warehouse::create([
            'name' => 'Gudang Pusat Semarang',
            'agent_id' => $agen->id,
            'address' => 'Semarang, Jawa Tengah',
        ]);

        $product = Product::create([
            'name' => 'Minyak Goreng 1L',
            'sku' => 'MG-1L',
            'category' => 'Sembako',
            'unit' => 'botol',
            'base_price' => 18000,
        ]);

        Outlet::create([
            'name' => 'Toko Sumber Rejeki',
            'owner_name' => 'Pak Slamet',
            'phone' => '081234567890',
            'address' => 'Jl. Pandanaran, Semarang',
            'latitude' => -6.9932,
            'longitude' => 110.4203,
            'agent_id' => $agen->id,
        ]);
    }
}
