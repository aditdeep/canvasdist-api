<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fee kurir ditentukan per Agen (kebijakan masing-masing agen ke kurir yang
 * bekerja untuknya) — dua komponen sekaligus, bisa dipakai salah satu atau
 * digabung: nominal tetap per pengiriman + persentase dari ongkir (shipping_fee)
 * order tersebut.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('courier_fee_flat', 15, 2)->default(0)->after('shipping_fee');
            $table->decimal('courier_fee_percent', 5, 2)->default(0)->after('courier_fee_flat');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['courier_fee_flat', 'courier_fee_percent']);
        });
    }
};
