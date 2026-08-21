<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Simpan ongkir aktual (bukan cuma estimasi tampilan) di order itu sendiri —
 * dibutuhkan supaya fee kurir berbasis persentase ongkir bisa dihitung akurat
 * per order, bukan cuma ditebak ulang dari data agen yang bisa berubah nanti.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('shipping_fee', 15, 2)->default(0)->after('total');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('shipping_fee');
        });
    }
};
