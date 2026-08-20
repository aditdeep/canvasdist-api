<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE commissions DROP CONSTRAINT IF EXISTS commissions_level_check");
        DB::statement("ALTER TABLE commissions ADD CONSTRAINT commissions_level_check CHECK (level IN ('wilayah','agen','reseller','platform'))");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE commissions DROP CONSTRAINT IF EXISTS commissions_level_check");
        DB::statement("ALTER TABLE commissions ADD CONSTRAINT commissions_level_check CHECK (level IN ('wilayah','agen','reseller'))");
    }
};
