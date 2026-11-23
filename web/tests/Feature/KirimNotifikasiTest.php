<?php

namespace Tests\Feature;

use App\Jobs\KirimNotifikasi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/** Job antrean + idempotensi (P14 slide 14). */
class KirimNotifikasiTest extends TestCase
{
    use RefreshDatabase;

    public function test_dijalankan_dua_kali_hanya_terkirim_sekali(): void
    {
        Log::spy();
        $job = new KirimNotifikasi('tamu-1', 'Ani', 'Terima kasih');

        $job->handle();
        $job->handle();   // seolah-olah worker mengulang job yang sama

        $this->assertDatabaseCount('notifikasi', 1);
        Log::shouldHaveReceived('info')
            ->withArgs(fn ($m) => str_contains($m, 'tamu-1 terkirim ke Ani'))->once();
        Log::shouldHaveReceived('info')
            ->withArgs(fn ($m) => str_contains($m, 'sudah pernah dikirim'))->once();
    }

    public function test_kunci_berbeda_terkirim_semua(): void
    {
        (new KirimNotifikasi('tamu-1', 'Ani', 'a'))->handle();
        (new KirimNotifikasi('tamu-2', 'Budi', 'b'))->handle();

        $this->assertDatabaseCount('notifikasi', 2);
    }

    public function test_dicoba_maksimum_tiga_kali(): void
    {
        $this->assertSame(3, (new KirimNotifikasi('k', 'a', 'b'))->tries);
    }
}
