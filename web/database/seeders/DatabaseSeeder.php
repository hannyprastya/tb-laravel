<?php

namespace Database\Seeders;

use App\Models\Tamu;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Data contoh untuk lab: php artisan db:seed --force
 * (di compose: docker compose exec app php artisan db:seed --force).
 * Jangan dijalankan di database produksi.
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Tanpa factory/faker: paket faker hanya ada di image uji, jadi seeder
        // ini harus jalan juga di image produksi (Render, k8s).
        User::firstOrCreate(
            ['email' => 'contoh@example.com'],
            ['name' => 'Pengguna Contoh', 'password' => Hash::make(Str::random(32))],
        );
        foreach (['Ani', 'Budi', 'Citra', 'Dodi', 'Eka'] as $nama) {
            Tamu::firstOrCreate(['nama' => $nama]);
        }

        // Slot modul kasus (web/KASUS.md): data contoh milik modul kasus.
        if (class_exists(KasusSeeder::class)) {
            $this->call(KasusSeeder::class);
        }
    }
}
