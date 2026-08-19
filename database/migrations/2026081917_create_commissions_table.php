<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('source_order_id')->constrained('orders')->cascadeOnDelete();
            $table->enum('level', ['wilayah','agen','reseller']);
            $table->decimal('percentage', 5, 2);
            $table->decimal('amount', 15, 2);
            $table->enum('status', ['pending','paid'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commissions');
    }
};
