<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promos', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['discount_percent','discount_fixed','tiered','points']);
            $table->decimal('value', 15, 2);
            $table->integer('min_qty')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('target_level', ['wilayah','agen','reseller','outlet'])->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promos');
    }
};
