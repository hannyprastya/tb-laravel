<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Buku tamu (fitur contoh starter), pengganti CREATE TABLE tamu di index.php.
return new class extends Migration
{
    public function up(): void
    {
        // Repo yang pindah dari buku tamu PHP (P5-P11) sudah punya tabel
        // "tamu" (kolom id, nama, waktu) di database yang sama (Neon atau
        // volume compose lama). Tabel itu tidak dihapus: namanya diganti
        // "tamu_lama" (beserta sequence dan primary key-nya).
        if (Schema::hasTable('tamu') && ! Schema::hasColumn('tamu', 'created_at')) {
            Schema::rename('tamu', 'tamu_lama');
            DB::statement('ALTER SEQUENCE IF EXISTS tamu_id_seq RENAME TO tamu_lama_id_seq');
            DB::statement('ALTER INDEX IF EXISTS tamu_pkey RENAME TO tamu_lama_pkey');
        }

        Schema::create('tamu', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 80);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tamu');
    }
};
