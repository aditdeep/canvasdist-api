<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rencana kolom: id, user_id, card_number, qr_code, level, timestamps
 * TODO: lengkapi kolom, foreign key, dan index sesuai kebutuhan final.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_cards', function (Blueprint $table) {
            $table->id();
            // TODO: tambahkan kolom sesuai daftar rencana di atas
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_cards');
    }
};
