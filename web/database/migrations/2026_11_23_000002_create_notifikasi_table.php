<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Catatan notifikasi yang sudah dikirim job KirimNotifikasi. Kolom kunci
// UNIQUE = titik idempotensi (P14 slide 14): job yang diulang dengan kunci
// sama tidak mengirim dua kali.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifikasi', function (Blueprint $table) {
            $table->id();
            $table->string('kunci', 100)->unique();
            $table->string('kepada', 120);
            $table->text('pesan');
            $table->timestamp('terkirim_pada')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifikasi');
    }
};
