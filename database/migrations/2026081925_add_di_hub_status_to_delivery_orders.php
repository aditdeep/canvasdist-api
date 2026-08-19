<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah status 'di_hub' — dipakai saat paket sudah sampai di titik transit
 * (hub) dan menunggu kurir etape berikutnya mengambil alih, sebelum
 * lanjut ke last-mile ('sampai_tujuan' -> 'selesai' seperti alur biasa).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Postgres tidak punya ALTER COLUMN ... ENUM langsung seperti MySQL,
        // jadi drop constraint lama dan buat constraint check baru.
        DB::statement("ALTER TABLE delivery_orders DROP CONSTRAINT IF EXISTS delivery_orders_status_check");
        DB::statement("ALTER TABLE delivery_orders ADD CONSTRAINT delivery_orders_status_check CHECK (status IN ('siap_kirim','dikirim','di_hub','sampai_tujuan','selesai'))");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE delivery_orders DROP CONSTRAINT IF EXISTS delivery_orders_status_check");
        DB::statement("ALTER TABLE delivery_orders ADD CONSTRAINT delivery_orders_status_check CHECK (status IN ('siap_kirim','dikirim','sampai_tujuan','selesai'))");
    }
};
