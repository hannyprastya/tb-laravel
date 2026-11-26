# Starter Laravel · Tugas Besar Cloud Computing

Aplikasi awal Tugas Besar mulai **P12**: Laravel 13 (PHP 8.4) + PostgreSQL
17, dengan login, antrean, tugas terjadwal, dan unggah berkas ke object
storage. Repo kelompok `tb-kelNo` pindah dari buku tamu PHP ke starter ini
(langkahnya di bagian [Pindah dari buku tamu](#pindah-dari-buku-tamu-p12)).
Fitur kasus kelompok dipasang kemudian sebagai **modul kasus**
([web/KASUS.md](web/KASUS.md)).

Starter ini menjaga kontrak aplikasi yang dipakai alat P10–P15: HTTP di
port `$PORT` (8080), `GET /health` (cek database), `GET /healthz` (tanpa
database), koneksi dari `DATABASE_URL`, log ke stderr, dan halaman ramah 503
saat database mati.

## Isi starter

| Fitur | Di mana | Pertemuan |
|-------|---------|-----------|
| Login, daftar, lupa password, profil (Laravel Breeze, Blade) | `web/routes/auth.php`, `web/resources/views/auth/` | – |
| Buku tamu: `/` (daftar + formulir), `POST /tamu`, `/tamu` (baca-saja, boleh di-cache), `/api/tamu` (JSON) | `web/app/Http/Controllers/BukuTamuController.php` | P10 |
| Identitas kelompok di halaman (pengganti `const PEMILIK`) | env `APP_PEMILIK` | – |
| `/health` (DB, batas 3 s) dan `/healthz` | `web/app/Http/Controllers/SehatController.php` | P8, P10 |
| Antrean: job `KirimNotifikasi` dengan kunci unik (idempotensi), service `worker` | `web/app/Jobs/` | P14 |
| Tugas terjadwal `rekap:harian`, service `scheduler` | `web/routes/console.php` | P14 |
| Unggah gambar/PDF ke disk `s3` (Floci di lab, Supabase Storage atau S3 lain di produksi), tautan unduh bertanda tangan 5 menit | `web/app/Http/Controllers/BerkasController.php` | P4, P14 |
| Halaman ramah 503 + `Retry-After: 60` saat DB mati, log `DB gagal: …` | `web/bootstrap/app.php` | P15 |
| Sesi, cache, dan antrean di PostgreSQL yang sama (cukup satu database) | `web/config/` | – |
| Antrean SQS di Floci (`QUEUE_CONNECTION=sqs`) | `web/config/queue.php` | P14 |
| Mode satu container (Apache + worker + scheduler) untuk Render Free | env `JALANKAN_WORKER=1` | P10, P15 |

## Peta folder
```
tb-kelNo/
├── README.md  docker-compose.yml                    # compose di akar: docker compose up tanpa opsi
├── .gitignore  trivy-secret.yaml  .trivyignore.yaml  # berlaku untuk seluruh repo (P12)
├── .github/        workflows/ci.yml  dependabot.yml
├── web/            aplikasi Laravel (semua yang masuk image)
│   ├── Dockerfile  Dockerfile.otel  .dockerignore  docker/   # image (P7, P11, P13)
│   ├── app/  bootstrap/  config/  database/  lang/  public/  resources/  routes/  tests/
│   └── KASUS.md                                     # cara memasang modul kasus
├── deploy/
│   ├── compose/    otel.yml  lb.yml  nginx.conf  workers.yml  apache-proses.conf
│   └── k8s/        00-rahasia.yaml … 50-hpa.yaml     # kind + HPA (P13)
├── infra/          Terraform per komponen (P14): storage-file/  antrean/
├── observability/  alert + dasbor SLO Grafana sebagai kode (P11)
├── tests/          fungsional.sh  pantau.sh  k6/
└── docs/           dokumen kelompok P9–P15 (milik kelompok, bukan dari starter)
```

| Folder | Isi |
|--------|-----|
| akar | `docker-compose.yml` (app, worker, scheduler, db, floci) dan aturan scan untuk seluruh repo: Trivy otomatis membaca `trivy-secret.yaml` dari folder kerja |
| `.github/` | pipeline CI/CD (P10, P12) dan Dependabot (P12) |
| `web/` | kode Laravel; konteks build `docker build web`; docroot hanya `web/public/` |
| `deploy/compose/` | lapisan compose lab: OpenTelemetry (P11), batas worker Apache (P11 B), load balancer + cache Nginx (P13) |
| `deploy/k8s/` | manifest kind: Secret, db, Job migrasi, web, worker + scheduler, HPA (P8, P13) |
| `infra/` | satu komponen Terraform per anggota (P14), diuji ke Floci |
| `observability/` | alert error 5xx dan dasbor SLO yang dimuat Grafana (P11) |
| `tests/` | uji fungsional F-01..F-20 (P10, P15), pemantau `/health` (P10 F.3), skrip k6 (P10, P11, P13) |
| `docs/` | dokumen kelompok; lihat [docs/README.md](docs/README.md) |

## Cara menjalankan

### Di GitHub Codespaces (jalur utama)
1. Di halaman repo: *Code › Codespaces › … › New with options*, mesin
   **2-core** (8 GB RAM, 32 GB disk) sudah cukup.
2. Di terminal codespace, dari akar repo:
   ```bash
   docker compose up -d --build --wait     # build pertama butuh beberapa menit
   bash tests/fungsional.sh                # semua F-xx harus "Lulus"
   ```
3. Tab *Ports*: buka port **8080** (aplikasi). Port 3000 (Grafana) dan 4566
   (Floci) ikut muncul saat dipakai.

Kebutuhan sumber daya (perkiraan):

| Yang dijalankan | RAM | Disk (image + cache build) |
|-----------------|-----|----------------------------|
| compose inti: app, worker, scheduler, db, floci | ±0,2–0,3 GB | ±3 GB |
| + OpenTelemetry (Grafana LGTM, image `tb-web:otel`) | +±0,8 GB | +±3,3 GB |
| + image uji (`docker compose run --rm uji`) | sesaat | +±0,7 GB |

Semuanya muat di codespace 2-core (8 GB RAM, sisa disk ±12 GB). Kuota
gratis dihitung per jam mesin menyala: **hentikan codespace** setelah
selesai (*Codespaces › Stop*), dan bersihkan image lama bila disk menipis:
`docker system prune` (hanya menghapus yang tidak dipakai container).

Bila CSS tidak termuat di URL Codespaces (halaman polos), jalankan ulang
dengan alamat publik codespace:
```bash
export APP_URL=https://${CODESPACE_NAME}-8080.${GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN} PAKSA_APP_URL=1
docker compose up -d --wait
```

### Laptop (jalur cadangan)
Docker Desktop atau OrbStack, lalu perintah yang sama dari akar repo.

### Perintah yang sering dipakai
Semua dari **akar repo**. `docker-compose.yml` tetap di akar, jadi
`docker compose up` jalan tanpa opsi; berkas compose lain di
`deploy/compose/` dipakai berlapis lewat `COMPOSE_FILE` (dipisah titik dua,
berkas pertama tetap `docker-compose.yml`).

```bash
# aplikasi lengkap (P7): http://localhost:8080
docker compose up -d --build --wait
curl -fsS localhost:8080/health          # {"status":"ok","db":"ok"}
bash tests/fungsional.sh                 # F-18..F-20 mematikan/membekukan db sebentar
bash tests/pantau.sh                     # jam + kode /health tiap 2 s (P10 F.3)
docker compose logs -f app worker        # log (stderr) aplikasi dan worker

# uji PHPUnit (database "uji" terpisah) dan Larastan level 5 (P12 C)
docker compose run --rm --build uji
docker compose run --rm uji vendor/bin/phpstan analyse --memory-limit=1G

# perintah artisan di container yang sedang jalan
docker compose exec app php artisan route:list
docker compose exec app php artisan db:seed --force       # data contoh
docker compose exec app php artisan rekap:harian          # rekap manual
JADWAL_REKAP="* * * * *" docker compose up -d --wait      # rekap tiap menit

# load test k6 (P10), hanya ke compose sendiri
docker run --rm -i --network tb-kelNo_default -e URL=http://app:8080 \
  grafana/k6:1.8.2 run --vus 25 --duration 1m - < tests/k6/uji.js

# OpenTelemetry + Grafana (P11): Grafana di http://localhost:3000
export NIM=NIM_KAMU COMPOSE_FILE=docker-compose.yml:deploy/compose/otel.yml
docker compose up -d --build --wait

# batas worker Apache (P11 B): lapisan di atas compose yang dipakai
COMPOSE_FILE=$COMPOSE_FILE:deploy/compose/workers.yml WORKERS=1 \
  docker compose up -d --wait

# load balancer + cache Nginx (P13 C.2-C.3), dua replika app
docker compose down
COMPOSE_FILE=docker-compose.yml:deploy/compose/lb.yml \
  docker compose up -d --build --wait --scale app=2
curl -sI localhost:8080/tamu | grep X-Cache-Status   # MISS lalu HIT

# antrean SQS di Floci (P14) sebagai ganti antrean database
QUEUE_CONNECTION=sqs docker compose up -d --wait

# mode satu container seperti di Render (worker + scheduler di dalam app)
JALANKAN_WORKER=1 docker compose up -d --wait --scale worker=0 --scale scheduler=0

# Kubernetes lokal (P13): kind + HPA
docker build -t tb-web:1.0 web && kind load docker-image tb-web:1.0 --name cc-lab
kubectl apply -f deploy/k8s/

# berhenti; -v juga menghapus data database dan kunci lab
docker compose down
```

Nama jaringan compose = nama folder + `_default` (`docker network ls`).
`APP_KEY` di compose dibuat otomatis sekali dan disimpan di volume `kunci`
(khusus lab, `KUNCI_LAB=1`); di Render dan Kubernetes `APP_KEY` wajib diisi
sendiri. Komponen Terraform: [infra/README.md](infra/README.md).

## Pindah dari buku tamu (P12)

Untuk kelompok yang sudah punya repo `tb-kelNo` dengan `docs/` P9–P11 dan
pipeline P10. Kerjakan di branch, lalu gabungkan lewat pull request seperti
biasa (P10 A).

1. **Branch baru:** `git switch -c pindah-laravel`.
2. **Hapus aplikasi lama**, dokumen tetap:
   ```bash
   git rm -r index.php composer.json composer.lock .htaccess Dockerfile \
     Dockerfile.otel .dockerignore docker-compose.otel.yml \
     docker-compose.workers.yml workers.conf docker-compose.lb.yml nginx.conf k8s
   ```
   (hapus hanya yang memang ada; repo yang memakai folder `app/` cukup
   `git rm -r app k8s deploy`). **Jangan** menghapus `docs/`.
3. **Salin starter** ke akar repo: `web/`, `deploy/`, `infra/`,
   `observability/`, `tests/`, `.github/`, `docker-compose.yml`,
   `trivy-secret.yaml`, `.trivyignore.yaml`, `.gitignore`, dan README ini
   (gabungkan dengan bagian anggota/kontribusi README lama). Folder `infra/`
   milik anggota yang sudah ada tetap dipakai, cukup ditambah.
4. **Identitas kelompok:** ganti nilai bawaan `APP_PEMILIK` di
   `docker-compose.yml` (mis. `Kelompok 3 - Klinik Pratama`), dan
   `nim-00000000` di `deploy/k8s/*.yaml` dengan namespace pemilik klaster.
5. **Database lama:** volume compose dari P7–P11 masih berisi tabel buku
   tamu. Migrasi starter mengganti nama tabel lama `tamu` menjadi
   `tamu_lama` (datanya tetap), jadi tidak perlu dihapus. Untuk memulai dari
   kosong: `docker compose down -v`.
6. **Uji lokal:** `docker compose up -d --build --wait`,
   `bash tests/fungsional.sh`, `docker compose run --rm --build uji`.
7. **CI:** `.github/workflows/ci.yml` starter menggantikan yang lama. Nama
   job tetap `build-test-deploy`, jadi ruleset `main` (P10 A.3) tidak perlu
   diubah. Secret `RENDER_DEPLOY_HOOK` dan variabel `RENDER_ENABLED` tetap.
8. **Render:** tambah environment variable baru (bagian
   [Render](#render-neon-dan-object-storage)): `APP_KEY`, `APP_PEMILIK`,
   `MIGRASI_SAAT_START=1`, `JALANKAN_WORKER=1`, dan `AWS_*`.
   `DATABASE_URL` Neon tetap sama.
9. **Dokumen:** catat perpindahan sebagai ADR baru di `docs/adr/` (alasan,
   akibat, apa yang berubah di pengujian), perbarui diagram container
   (`web`, `worker`, `scheduler`, object storage) dan Tabel 1 uji fungsional
   (`tests/fungsional.sh` sekarang F-01..F-20).
10. Buka pull request; pipeline harus hijau sebelum di-merge.

## Padanan langkah modul (buku tamu) → starter Laravel

Modul P10–P15 tetap memakai buku tamu sebagai latihan alat. Di repo
kelompok setelah P12, langkah yang sama dikerjakan di starter seperti tabel
ini. Hasil yang dicatat di laporan diambil dari starter.

| P | Langkah modul (buku tamu) | Di starter Laravel |
|---|---------------------------|--------------------|
| P10 | `docker build -t NAMA .` | `docker build -t NAMA web` (konteks `web/`) |
| P10 | test di CI: `compose up --wait` + `curl -f /health` | sama, lalu `bash tests/fungsional.sh` (F-01..F-20) |
| P10 | F-02 `POST /tamu` dengan `curl -d nama=…` | formulir butuh token CSRF: `tests/fungsional.sh` mengambil token dulu (F-05); tanpa token = 419 (F-07). Untuk curl/k6 pakai `POST /api/tamu` |
| P10 | F-03 nama kosong → 400 | validasi Laravel: 302 kembali ke formulir + pesan; dengan `Accept: application/json` → 422 (F-06, F-17) |
| P10 | F-04 nama berupa array (baris 42 `index.php`, `is_string`) | aturan `'string'` di `$request->validate()` + Feature test `BukuTamuTest::test_nama_berupa_array_ditolak` |
| P10 | k6 ke `/` | `tests/k6/uji.js` bawaan ke `/tamu` (publik); `-e RUTE=/` atau `-e RUTE=/api/tamu` |
| P10 | Render: satu web service dari image GHCR | sama, ditambah env Laravel dan `JALANKAN_WORKER=1` (worker + scheduler di container yang sama) |
| P11 | `Dockerfile.otel` + `otel.php` (`auto_prepend_file`) | `web/Dockerfile.otel`: ekstensi `opentelemetry` + paket `opentelemetry-auto-laravel`; span otomatis per rute (`GET /tamu`), query (`sql SELECT`), dan job (`process …`) |
| P11 | `docker-compose.otel.yml` | `deploy/compose/otel.yml`; service `worker-NIM` ikut mengirim trace job |
| P11 | rute `/lambat` | tidak ada; bottleneck dicari dari rute kasus atau `/` (sesi + query) |
| P11 | `error_log()` ke Loki | `Log::info()` / `Log::error()` Laravel, otomatis ke Loki dengan `trace_id` |
| P11 B | `workers.conf` | `deploy/compose/workers.yml` + `apache-proses.conf`; image sudah membaca env `WORKERS` |
| P12 A | temuan T-01 `/vendor/composer/installed.json` → `.htaccess` | docroot hanya `web/public/` (`web/docker/apache.conf`); `vendor/`, `.env`, `composer.lock` di luar docroot |
| P12 C | `composer audit` di akar | di `web/` (`-v "$PWD/web:/app"`), ditambah `npm audit` untuk aset Vite |
| P12 C | PHPStan level 5 `index.php` | Larastan level 5 di `web/` (`web/phpstan.neon`), dijalankan dari image uji |
| P12 C | Semgrep `p/default` | sama, dari akar repo |
| P12 D | `trivy-secret.yaml` aturan password | aturan disesuaikan untuk kode Laravel (`Password::defaults()` dan rute `'password'` bukan password) + aturan `APP_KEY`; allow-rule hanya `latihan` |
| P12 F | Trivy image di CI | sama; base image `php:8.4-apache`, `composer`, `node` dikunci digest |
| P13 C.1 | `WORKERS` / `MaxRequestWorkers` | sama (env `WORKERS`, bawaan 4) |
| P13 C.2-C.3 | `docker-compose.lb.yml` berdiri sendiri, cache `/` dan `/buku` | `deploy/compose/lb.yml` sebagai lapisan (`--scale app=2`); cache **hanya halaman anonim** ber-`Cache-Control: public` (grup `publik`, mis. `/tamu`); pengunjung dengan cookie sesi = BYPASS (penjelasan di `nginx.conf`) |
| P13 C.4 | hardening: `.htaccess`, `ServerTokens Prod`, `expose_php Off`, `USER www-data` | sudah di `web/Dockerfile`; kode milik root, yang bisa ditulis hanya `storage/` dan `bootstrap/cache` |
| P13 A | `k8s/app.yaml`, `k8s/hpa.yaml` | `deploy/k8s/` (Secret, db, Job migrasi, web, worker + scheduler, HPA) |
| P14 E | `infra/<komponen>` | sama; `infra/storage-file` = bucket unggahan, `infra/antrean` = antrean SQS + DLQ untuk `QUEUE_CONNECTION=sqs` |
| P14 F | SQS + fungsi PHP (Bref) | antrean Laravel ke SQS Floci (`QUEUE_CONNECTION=sqs`), worker = konsumen; idempotensi di `KirimNotifikasi` |
| P15 C | `db()` dengan `PDO::ATTR_TIMEOUT => 3` | `web/config/database.php` (`DB_TIMEOUT`, bawaan 3) |
| P15 C | `maaf()` → 503 + `Retry-After` | exception handler di `web/bootstrap/app.php` + `web/app/Support/DbGagal.php`; view `errors/db-gagal` |
| P15 E | `docker compose stop db` | sama (F-18); `docker compose pause db` = DB macet, `/health` menyerah dalam 3 s (F-19), halaman biasa tetap 503 ramah (F-20) |

## Render, Neon, dan object storage

Render Free hanya memberi satu web service, jadi starter berjalan dalam
**mode satu container**: `JALANKAN_WORKER=1` membuat supervisord menyalakan
Apache, queue worker, dan scheduler di container yang sama.

1. Buat `APP_KEY` sekali (simpan, jangan di-commit):
   ```bash
   docker run --rm --entrypoint php ghcr.io/PEMILIK/tb-kelno:TAG artisan key:generate --show
   ```
2. Render › web service (image GHCR, P10 C.2) › *Environment*:

   | Variabel | Nilai |
   |----------|-------|
   | `APP_KEY` | hasil langkah 1 (`base64:…`) |
   | `APP_PEMILIK` | nama kelompok dan kasus |
   | `APP_URL` | `https://NAMA.onrender.com` |
   | `DATABASE_URL` | connection string **pooled** Neon, berakhir `?sslmode=require` |
   | `MIGRASI_SAAT_START` | `1` (migrasi otomatis saat deploy) |
   | `JALANKAN_WORKER` | `1` |
   | `AWS_ENDPOINT`, `AWS_DEFAULT_REGION`, `AWS_BUCKET`, `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_USE_PATH_STYLE_ENDPOINT=true` | dari provider object storage (langkah 4) |

3. *Settings › Health Check Path*: `/health`.
4. Object storage S3-compatible, contoh **Supabase Storage**: buat bucket
   privat (mis. `tb-berkas`), lalu *Project Settings › Storage › S3
   Connection*: salin *Endpoint* persis seperti di dasbor (bentuknya
   `https://PROJECT_REF.storage.supabase.co/storage/v1/s3`, dengan path
   `/storage/v1/s3`) ke `AWS_ENDPOINT`, *Region* ke `AWS_DEFAULT_REGION`, dan buat *S3 access
   key* untuk `AWS_ACCESS_KEY_ID` / `AWS_SECRET_ACCESS_KEY`. Provider lain
   (Cloudflare R2, Backblaze B2, AWS S3) cukup mengganti nilai yang sama.
   Berkas diunduh lewat aplikasi (tautan bertanda tangan 5 menit), jadi
   bucket tidak perlu publik.
5. Neon: tabel Laravel dibuat di database yang sama dengan buku tamu; tabel
   lama `tamu` otomatis menjadi `tamu_lama`.

Akibat Render Free yang perlu dicatat di dokumen: layanan tidur setelah ±15
menit tanpa trafik, dan selama tidur worker serta scheduler ikut berhenti
(job menunggu di tabel `jobs`, rekap terjadwal terlewat). Email (lupa
password) hanya ditulis ke log (`MAIL_MAILER=log`).

## CI/CD (`.github/workflows/ci.yml`)
Setiap PR dan push ke `main` menjalankan job `build-test-deploy`:
`composer audit` + `npm audit` → Semgrep `p/default` → `trivy config` dan
`trivy fs --scanners secret` → image uji: Larastan level 5 dan `php artisan
test` (PostgreSQL service) → build image produksi dari `web/` → compose +
`tests/fungsional.sh` → Trivy image (gagal bila CRITICAL yang sudah ada
perbaikannya) → **push ke GHCR** (hanya `main`, `GITHUB_TOKEN` bawaan) →
**deploy ke Render** (hanya bila diaktifkan). Semua action dikunci SHA,
izin token `contents: read, packages: write`.

**Tanpa secret apa pun pipeline tetap hijau:** langkah Deploy dilewati.
Untuk mengaktifkan deploy (pemilik repo):
1. Buat web service Render dari image GHCR (P10 C.2), paket GHCR dijadikan
   *Public* (P10 B.5), isi environment seperti bagian Render di atas.
2. *Settings › Secrets and variables › Actions › Secrets*:
   `RENDER_DEPLOY_HOOK` = URL deploy hook Render.
3. Tab *Variables* di halaman yang sama: `RENDER_ENABLED` = `true`.
4. *Settings › Rules › Rulesets*: `main` wajib PR + check `build-test-deploy`
   (P10 A.3).

## Modul kasus
Folder `web/routes/kasus.php`, `web/app/Http/Controllers/Kasus/`,
`web/database/migrations/`, dan `web/resources/views/kasus/` adalah tempat
fitur kasus. Aturan, contoh modul lengkap, dan syarat lulusnya:
[web/KASUS.md](web/KASUS.md).

## Yang perlu diketahui
- Data Floci ada di memori: setelah container `floci` dinyalakan ulang,
  bucket dibuat lagi oleh `app` saat start, tetapi isi berkas lama hilang
  (data di tabel `berkas` tetap, tautan unduhnya gagal). Di produksi berkas
  ada di object storage sungguhan.
- Kode ada di dalam image: setiap perubahan di `web/` butuh
  `docker compose up -d --build --wait`.
- Halaman grup `publik` (mis. `/tamu`) bisa basi paling lama 60 detik bila
  lewat cache Nginx; halaman dengan sesi tidak pernah di-cache.
- Password `latihan` dan kunci Floci `test` hanya untuk lab lokal dan CI.
  Produksi memakai `APP_KEY`, `DATABASE_URL`, dan kunci storage dari
  environment Render, bukan dari berkas.
