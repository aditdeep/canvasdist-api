<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah role 'customer' (end-user pembeli di storefront) dan kolom outlet_id
 * di users — tiap customer otomatis punya 1 "outlet" yang merepresentasikan
 * alamat pengirimannya, supaya bisa reuse infrastruktur Order/DeliveryOrder/
 * komisi yang sudah ada tanpa membuat model terpisah.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check");
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('super_admin','wilayah','agen','reseller','sales','gudang','kurir','customer'))");

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('outlet_id')->nullable()->after('push_token')->constrained('outlets')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('outlet_id');
        });

        DB::statement("ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check");
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('super_admin','wilayah','agen','reseller','sales','gudang','kurir'))");
    }
};
