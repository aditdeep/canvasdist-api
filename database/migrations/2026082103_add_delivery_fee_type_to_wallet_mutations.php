<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE wallet_mutations DROP CONSTRAINT IF EXISTS wallet_mutations_type_check");
        DB::statement("ALTER TABLE wallet_mutations ADD CONSTRAINT wallet_mutations_type_check CHECK (type IN ('topup','payment','commission','cashback','refund','delivery_fee'))");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE wallet_mutations DROP CONSTRAINT IF EXISTS wallet_mutations_type_check");
        DB::statement("ALTER TABLE wallet_mutations ADD CONSTRAINT wallet_mutations_type_check CHECK (type IN ('topup','payment','commission','cashback','refund'))");
    }
};
