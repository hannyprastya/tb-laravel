# web/ · aplikasi Laravel

Kode aplikasi starter (Laravel 13, PHP 8.4, PostgreSQL). Semua yang ada di
folder ini masuk image (`docker build web`), kecuali yang dikecualikan
`.dockerignore`. Cara menjalankan, peta folder, dan konfigurasi produksi:
[README di akar repo](../README.md). Cara memasang modul kasus:
[KASUS.md](KASUS.md).

| Path | Isi |
|------|-----|
| `app/Http/Controllers/` | buku tamu, berkas, `/health`; `Auth/` dari Breeze; `Kasus/` untuk modul kasus |
| `app/Jobs/KirimNotifikasi.php` | contoh job antrean + idempotensi |
| `app/Console/Commands/` | `rekap:harian` (terjadwal), `lab:siapkan` (bucket/antrean Floci) |
| `app/Support/DbGagal.php` | mengenali "database tidak bisa dihubungi" (halaman 503 ramah) |
| `bootstrap/app.php` | rute, grup `publik`, TrustProxies, exception handler DB |
| `config/` | `database.php` (`DATABASE_URL`, batas 3 s), `filesystems.php` (disk `s3`), `queue.php` (SQS) |
| `routes/` | `web.php`, `publik.php`, `api.php`, `sehat.php`, `console.php` (+ `kasus.php` dari modul) |
| `resources/views/` | Blade (Tailwind); `kasus/` untuk modul kasus |
| `lang/id/`, `lang/id.json` | teks dan pesan validasi bahasa Indonesia |
| `tests/` | PHPUnit (`php artisan test`) |
| `docker/` | konfigurasi Apache, PHP, supervisord, skrip start `mulai.sh` |
| `Dockerfile`, `Dockerfile.otel` | image produksi dan image lab OpenTelemetry |
