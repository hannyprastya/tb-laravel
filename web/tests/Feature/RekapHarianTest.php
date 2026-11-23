<?php

namespace Tests\Feature;

use App\Models\Tamu;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Tugas terjadwal rekap:harian (P14 slide 16). */
class RekapHarianTest extends TestCase
{
    use RefreshDatabase;

    public function test_rekap_menghitung_tamu_hari_ini(): void
    {
        Tamu::factory()->count(2)->create();

        $this->artisan('rekap:harian')
            ->expectsOutputToContain('tamu_hari_ini=2')
            ->assertSuccessful();
    }

    public function test_rekap_terdaftar_di_jadwal(): void
    {
        $jadwal = collect(app(Schedule::class)->events())
            ->first(fn ($e) => $e->description === 'rekap:harian');

        $this->assertNotNull($jadwal);
        $this->assertSame('55 23 * * *', $jadwal->expression);
    }
}
