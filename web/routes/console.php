<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Jadwal tugas (P14 slide 16, cron). Dijalankan service scheduler
// (php artisan schedule:work) atau supervisord bila JALANKAN_WORKER=1.
// Lihat daftar dan jadwal berikutnya: php artisan schedule:list
// JADWAL_REKAP memakai format cron 5 kolom, zona waktu APP_TIMEZONE.
//
// Schedule::call (bukan Schedule::command) supaya rekap berjalan di dalam
// proses scheduler dan baris log "REKAP ..." ikut tampil di docker logs.
Schedule::call(fn () => Artisan::call('rekap:harian'))
    ->name('rekap:harian')
    ->cron((string) config('app.jadwal_rekap'))
    ->withoutOverlapping()
    ->onOneServer();   // bila scheduler lebih dari satu, hanya satu yang jalan

// Slot modul kasus (web/KASUS.md): jadwal tambahan milik modul kasus.
if (is_file(__DIR__.'/kasus-jadwal.php')) {
    require __DIR__.'/kasus-jadwal.php';
}
