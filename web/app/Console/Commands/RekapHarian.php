<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Contoh tugas terjadwal (cron, P14 slide 16): rekap jumlah data hari ini
 * ditulis ke log. Jadwalnya di routes/console.php, dijalankan service
 * scheduler (php artisan schedule:work). Bisa juga dijalankan manual:
 *   php artisan rekap:harian
 */
class RekapHarian extends Command
{
    protected $signature = 'rekap:harian';

    protected $description = 'Tulis rekap harian (tamu, notifikasi, berkas) ke log';

    public function handle(): int
    {
        $hariIni = now()->startOfDay();
        $angka = [
            'tamu_hari_ini' => DB::table('tamu')->where('created_at', '>=', $hariIni)->count(),
            'tamu_total' => DB::table('tamu')->count(),
            'notifikasi_hari_ini' => DB::table('notifikasi')->where('terkirim_pada', '>=', $hariIni)->count(),
            'berkas_total' => DB::table('berkas')->count(),
            'job_gagal' => DB::table('failed_jobs')->count(),
        ];

        $teks = 'REKAP '.$hariIni->toDateString().': '
            .collect($angka)->map(fn ($n, $k) => "$k=$n")->implode(' ');
        Log::info($teks, $angka);
        $this->info($teks);

        return self::SUCCESS;
    }
}
