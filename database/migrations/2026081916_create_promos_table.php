<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rencana kolom: id, name, type, value, start_date, end_date, target_level, timestamps
 * TODO: lengkapi kolom, foreign key, dan index sesuai kebutuhan final.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promos', function (Blueprint $table) {
            $table->id();
            // TODO: tambahkan kolom sesuai daftar rencana di atas
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promos');
    }
};
