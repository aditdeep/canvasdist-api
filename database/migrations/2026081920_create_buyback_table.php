<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('buyback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->nullable()->constrained('visits')->nullOnDelete();
            $table->foreignId('outlet_id')->constrained();
            $table->string('item_type');
            $table->integer('qty');
            $table->decimal('unit_price', 15, 2);
            $table->decimal('cashback_amount', 15, 2);
            $table->string('photo_path')->nullable();
            $table->enum('status', ['pending','verified','rejected'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('buyback');
    }
};
