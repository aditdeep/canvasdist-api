<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('app_name')->default('Super OEY');
            $table->string('logo_path')->nullable();
            $table->string('owner_name')->nullable();
            $table->string('owner_email')->nullable();
            $table->string('owner_phone')->nullable();
            $table->decimal('platform_fee_percent', 5, 2)->default(0);
            $table->foreignId('platform_owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        DB::table('settings')->insert([
            'app_name' => 'Super OEY',
            'platform_fee_percent' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
