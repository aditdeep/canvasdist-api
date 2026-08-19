<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rencana kolom: id, name, owner_name, phone, address, latitude, longitude, agent_id, timestamps
 * TODO: lengkapi kolom, foreign key, dan index sesuai kebutuhan final.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outlets', function (Blueprint $table) {
            $table->id();
            // TODO: tambahkan kolom sesuai daftar rencana di atas
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outlets');
    }
};
