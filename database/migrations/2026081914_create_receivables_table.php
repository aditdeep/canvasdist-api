<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rencana kolom: id, invoice_id, outlet_id, amount, paid_amount, status, timestamps
 * TODO: lengkapi kolom, foreign key, dan index sesuai kebutuhan final.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receivables', function (Blueprint $table) {
            $table->id();
            // TODO: tambahkan kolom sesuai daftar rencana di atas
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receivables');
    }
};
