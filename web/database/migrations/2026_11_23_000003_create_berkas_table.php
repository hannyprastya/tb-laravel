<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Data berkas unggahan. Isi berkasnya di object storage (disk s3); tabel
// ini hanya menyimpan siapa pemiliknya dan di mana letaknya.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('berkas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('nama_tampil', 120);   // hanya untuk ditampilkan
            $table->string('path')->unique();      // nama acak buatan server
            $table->string('mime', 100);
            $table->unsignedInteger('ukuran');     // byte
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('berkas');
    }
};
