<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * shipping_fee di users (role agen) — ongkir flat yang dikenakan agen untuk
 * pengiriman storefront ke customer di wilayahnya. Pendekatan sederhana
 * (bukan hitung jarak), bisa diperluas nanti kalau perlu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('shipping_fee', 15, 2)->default(0)->after('outlet_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('shipping_fee');
        });
    }
};
