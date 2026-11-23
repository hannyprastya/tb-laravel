<?php

namespace Database\Seeders;

use App\Models\Tamu;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

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
        User::factory()->create([
            'name' => 'Pengguna Contoh',
            'email' => 'contoh@example.com',
        ]);
        Tamu::factory()->count(5)->create();

        // Slot modul kasus (web/KASUS.md): data contoh milik modul kasus.
        if (class_exists(KasusSeeder::class)) {
            $this->call(KasusSeeder::class);
        }
    }
}
