<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_legs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_order_id')->constrained('delivery_orders')->cascadeOnDelete();
            $table->unsignedInteger('sequence'); // urutan etape: 1, 2, 3, dst
            $table->foreignId('from_hub_id')->nullable()->constrained('hubs')->nullOnDelete();
            // to_hub_id NULL artinya etape ini adalah last-mile langsung ke outlet
            // (bukan transit ke hub lain) — pakai flow status/POD DeliveryOrder yang sudah ada.
            $table->foreignId('to_hub_id')->nullable()->constrained('hubs')->nullOnDelete();
            $table->foreignId('courier_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['pending', 'in_transit', 'arrived'])->default('pending');
            $table->timestamp('departed_at')->nullable();
            $table->timestamp('arrived_at')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_legs');
    }
};
