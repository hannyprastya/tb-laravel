<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Contoh job antrean (P14 slide 10-16): "mengirim" notifikasi dengan
 * menulis ke log. Dijalankan worker (php artisan queue:work --tries=3).
 *
 * Idempotensi: setiap notifikasi punya $kunci unik (mis. "tamu-15"). Bila
 * job diulang (worker mati di tengah jalan, timeout, atau pesan SQS
 * terkirim dua kali), baris kedua ditolak UNIQUE dan notifikasi tidak
 * dikirim lagi.
 */
class KirimNotifikasi implements ShouldQueue
{
    use Queueable;

    /** Percobaan maksimum; setelah itu job masuk tabel failed_jobs. */
    public int $tries = 3;

    /** Jeda sebelum percobaan ke-2 dan ke-3 (detik). */
    public array $backoff = [5, 30];

    public function __construct(
        public string $kunci,
        public string $kepada,
        public string $pesan,
    ) {}

    public function handle(): void
    {
        // Catat dulu, kirim kemudian, dalam SATU transaksi: bila pengiriman
        // gagal (exception), catatan ikut dibatalkan dan percobaan
        // berikutnya mengirim lagi; bila sudah tercatat, tidak dikirim lagi.
        DB::transaction(function () {
            $baru = DB::table('notifikasi')->insertOrIgnore([
                'kunci' => $this->kunci,
                'kepada' => $this->kepada,
                'pesan' => $this->pesan,
                'terkirim_pada' => now(),
            ]);

            if ($baru === 0) {
                Log::info("NOTIFIKASI {$this->kunci} sudah pernah dikirim, dilewati");

                return;
            }

            // Di aplikasi sungguhan: kirim email/WhatsApp lewat API di sini.
            Log::info("NOTIFIKASI {$this->kunci} terkirim ke {$this->kepada}: {$this->pesan}");
        });
    }
}
