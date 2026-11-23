# Slot modul kasus

Starter ini sudah jalan sendiri (buku tamu, login, unggah berkas, antrean,
jadwal). Fitur kasus kelompok (salah satu dari 10 kasus Tugas Besar)
dipasang sebagai **modul kasus**: satu folder yang isinya disalin ke `web/`,
lalu image dibangun ulang. Migrasi database berjalan otomatis saat container
`app` menyala (`MIGRASI_SAAT_START=1`).

```bash
# dari akar repo; kasus-03-klinik = contoh nama folder modul
cp -R kasus-03-klinik/. web/
docker compose up -d --build --wait      # build ulang + php artisan migrate
bash tests/fungsional.sh                 # uji dasar + uji modul (tests/kasus.sh)
docker compose run --rm --build uji      # php artisan test (termasuk uji modul)
```

Kode ada di dalam image (tidak di-mount), jadi setiap perubahan kode modul
butuh `docker compose up -d --build --wait` lagi.

## 1. Isi folder modul

Path di dalam folder modul = path di dalam `web/`. Hanya berkas di daftar
ini yang boleh ada, supaya modul tidak menimpa berkas starter.

| Path di modul | Wajib | Isi |
|---------------|-------|-----|
| `routes/kasus.php` | ya | semua rute modul (halaman dan API), lihat bagian 2 |
| `app/Http/Controllers/Kasus/*.php` | ya | controller, namespace `App\Http\Controllers\Kasus` |
| `app/Models/Kasus/*.php` | bila ada tabel | model, namespace `App\Models\Kasus` |
| `database/migrations/2026_12_01_NNNNNN_*.php` | bila ada tabel | migrasi; tanggal awalan **setelah** `2026_11_23_000003` supaya jalan sesudah tabel starter |
| `resources/views/kasus/*.blade.php` | ya | view modul (`view('kasus.daftar')`) |
| `resources/views/kasus/menu.blade.php` | ya | tautan menu setelah login, lihat bagian 4 |
| `resources/views/kasus/dasbor.blade.php` | ya | kartu di halaman Dasbor |
| `tests/Feature/Kasus/*Test.php` | ya | uji PHPUnit modul, namespace `Tests\Feature\Kasus` |
| `tests/kasus.sh` | ya | uji fungsional curl, di-*source* oleh `tests/fungsional.sh` |
| `routes/kasus-jadwal.php` | bila ada tugas terjadwal | `Schedule::...` milik modul (dimuat `routes/console.php`) |
| `database/seeders/KasusSeeder.php` | bila ada data contoh | class `Database\Seeders\KasusSeeder` (dipanggil `DatabaseSeeder`) |
| `app/Jobs/Kasus/*.php` | bila ada job antrean | job, namespace `App\Jobs\Kasus` |
| `app/Console/Commands/Kasus/*.php` | bila ada perintah artisan | otomatis terdaftar |
| `config/kasus.php` | bila ada pengaturan | dibaca `config('kasus.kunci')`; nilai dari `env()` |

Berkas starter yang **tidak boleh** disentuh modul: `bootstrap/`,
`routes/web.php`, `routes/api.php`, `routes/publik.php`, `routes/sehat.php`,
`routes/console.php`, `config/*.php` selain `config/kasus.php`,
`composer.json`, `package.json`, `Dockerfile*`, `docker/`, `phpunit.xml`,
`phpstan.neon`, berkas di `app/` dan `resources/views/` di luar folder
`Kasus/` dan `kasus/`. Paket Composer atau npm tambahan tidak dipakai:
semua yang dibutuhkan 10 kasus sudah ada (Eloquent, validasi, antrean,
jadwal, disk `s3`, Blade + Tailwind + Alpine).

## 2. `routes/kasus.php`: tiga grup rute

`bootstrap/app.php` memuat berkas ini bila ada, **tanpa** grup middleware.
Setiap rute wajib masuk salah satu grup berikut.

| Grup | Untuk | Sesi/cookie | CSRF | Cache Nginx |
|------|-------|-------------|------|-------------|
| `web` | halaman dengan formulir, halaman setelah login | ya | ya (`@csrf` di formulir) | tidak pernah |
| `publik` | halaman GET baca-saja yang sama untuk semua pengunjung | tidak | tidak ada formulir | boleh (60 s) |
| `api` (+ awalan `/api`) | JSON untuk curl, k6, uji fungsional | tidak | tidak | tidak |

Aturan nama:
- URL modul memakai awalan sendiri, mis. `/klinik/...` dan `/api/klinik/...`.
  Alamat yang sudah dipakai starter: `/`, `/tamu`, `/berkas`, `/dashboard`,
  `/profile`, `/login`, `/register`, `/logout`, `/forgot-password`,
  `/reset-password`, `/verify-email`, `/email`, `/confirm-password`,
  `/password`, `/health`, `/healthz`, `/api/tamu`, `/build`.
- Nama rute berawalan `kasus.` (mis. `kasus.klinik.index`).
- Nama tabel tidak boleh sama dengan tabel starter: `users`, `sessions`,
  `password_reset_tokens`, `cache`, `cache_locks`, `jobs`, `job_batches`,
  `failed_jobs`, `migrations`, `tamu`, `notifikasi`, `berkas`.
- Rute API yang **mengubah data** tanpa login hanya untuk data yang memang
  boleh dikirim siapa saja (seperti buku tamu). Data milik pengguna diubah
  lewat halaman grup `web` + `auth`.
- Tidak memakai closure di rute (`Route::get('/x', function () {...})`):
  pakai controller atau `Route::view`, supaya `route:cache` saat start jalan.

## 3. Contoh modul lengkap (Kasus 0 · Pengumuman kampus)

Contoh terkecil yang memakai ketiga grup. Folder `kasus-00-pengumuman/`:

`routes/kasus.php`
```php
<?php

use App\Http\Controllers\Kasus\PengumumanController;
use Illuminate\Support\Facades\Route;

// Halaman (sesi + CSRF); kelola hanya setelah login
Route::middleware('web')->group(function () {
    Route::middleware('auth')->group(function () {
        Route::get('/pengumuman/kelola', [PengumumanController::class, 'kelola'])
            ->name('kasus.pengumuman.kelola');
        Route::post('/pengumuman', [PengumumanController::class, 'simpan'])
            ->name('kasus.pengumuman.simpan');
    });
});

// Halaman publik baca-saja, boleh di-cache
Route::middleware('publik')->group(function () {
    Route::get('/pengumuman', [PengumumanController::class, 'daftar'])
        ->name('kasus.pengumuman.daftar');
});

// JSON untuk k6 dan uji fungsional: /api/pengumuman
Route::middleware('api')->prefix('api')->group(function () {
    Route::get('/pengumuman', [PengumumanController::class, 'json']);
});
```

`database/migrations/2026_12_01_000001_create_pengumuman_table.php`
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengumuman', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('judul', 120);
            $table->text('isi');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengumuman');
    }
};
```

`app/Models/Kasus/Pengumuman.php`
```php
<?php

namespace App\Models\Kasus;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property string $judul
 * @property string $isi
 * @property \Illuminate\Support\Carbon $created_at
 */
class Pengumuman extends Model
{
    protected $table = 'pengumuman';

    protected $fillable = ['user_id', 'judul', 'isi'];
}
```

`app/Http/Controllers/Kasus/PengumumanController.php`
```php
<?php

namespace App\Http\Controllers\Kasus;

use App\Http\Controllers\Controller;
use App\Models\Kasus\Pengumuman;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PengumumanController extends Controller
{
    public function daftar(): View
    {
        return view('kasus.daftar', ['pengumuman' => Pengumuman::query()->latest('id')->limit(20)->get()]);
    }

    public function kelola(): View
    {
        return view('kasus.kelola');
    }

    public function simpan(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'judul' => ['required', 'string', 'max:120'],
            'isi' => ['required', 'string', 'max:2000'],
        ]);
        Pengumuman::create($data + ['user_id' => $request->user()?->id]);

        return redirect()->route('kasus.pengumuman.kelola')->with('status', 'Pengumuman tersimpan.');
    }

    public function json(): JsonResponse
    {
        return response()->json(['data' => Pengumuman::query()->latest('id')->limit(20)->get(['id', 'judul', 'created_at'])]);
    }
}
```

`resources/views/kasus/daftar.blade.php` (grup `publik`: tanpa `@csrf`,
tanpa `auth()`, memakai tata letak `x-publik-layout` dengan `:statis="true"`)
```blade
<x-publik-layout judul="Pengumuman" :statis="true">
    <h1 class="text-2xl font-semibold">Pengumuman</h1>
    <ul class="mt-6 divide-y divide-gray-200 rounded bg-white shadow-sm">
        @forelse ($pengumuman as $p)
            <li class="px-4 py-2"><b>{{ $p->judul }}</b><br>{{ $p->isi }}</li>
        @empty
            <li class="px-4 py-2 text-gray-500">Belum ada pengumuman.</li>
        @endforelse
    </ul>
</x-publik-layout>
```

`resources/views/kasus/kelola.blade.php` (grup `web` + `auth`: tata letak
`x-app-layout` milik Breeze, formulir wajib `@csrf`)
```blade
<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">Kelola pengumuman</h2></x-slot>
    <div class="py-12 max-w-3xl mx-auto sm:px-6 lg:px-8">
        @if (session('status'))
            <p class="rounded bg-green-50 p-3 text-green-800">{{ session('status') }}</p>
        @endif
        <form method="POST" action="{{ route('kasus.pengumuman.simpan') }}" class="mt-4 space-y-3 bg-white p-6 shadow-sm sm:rounded-lg">
            @csrf
            <x-text-input name="judul" :value="old('judul')" placeholder="Judul" class="w-full" required />
            <x-input-error :messages="$errors->get('judul')" />
            <textarea name="isi" class="w-full rounded-md border-gray-300" required>{{ old('isi') }}</textarea>
            <x-input-error :messages="$errors->get('isi')" />
            <x-primary-button>Simpan</x-primary-button>
        </form>
    </div>
</x-app-layout>
```

`resources/views/kasus/menu.blade.php` (lihat bagian 4) dan
`resources/views/kasus/dasbor.blade.php`
```blade
@if (($gaya ?? 'desktop') === 'ponsel')
    <x-responsive-nav-link :href="route('kasus.pengumuman.kelola')" :active="request()->routeIs('kasus.*')">Pengumuman</x-responsive-nav-link>
@else
    <x-nav-link :href="route('kasus.pengumuman.kelola')" :active="request()->routeIs('kasus.*')">Pengumuman</x-nav-link>
@endif
```
```blade
<a href="{{ route('kasus.pengumuman.kelola') }}" class="block bg-white shadow-sm sm:rounded-lg p-6 hover:bg-gray-50">
    <h3 class="font-semibold">Pengumuman</h3>
    <p class="text-sm text-gray-600">Tulis dan lihat pengumuman kampus.</p>
</a>
```

`tests/Feature/Kasus/PengumumanTest.php`
```php
<?php

namespace Tests\Feature\Kasus;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PengumumanTest extends TestCase
{
    use RefreshDatabase;

    public function test_tulis_lalu_tampil_di_halaman_publik_dan_api(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/pengumuman', ['judul' => 'Libur', 'isi' => 'Kampus libur Jumat.'])
            ->assertRedirect('/pengumuman/kelola');

        $this->get('/pengumuman')->assertOk()->assertSee('Libur')
            ->assertHeader('Cache-Control', 'max-age=60, public');
        $this->getJson('/api/pengumuman')->assertOk()->assertJsonPath('data.0.judul', 'Libur');
    }

    public function test_tamu_tidak_boleh_menulis(): void
    {
        $this->post('/pengumuman', ['judul' => 'x', 'isi' => 'y'])->assertRedirect('/login');
    }
}
```

`tests/kasus.sh` (di-*source* di akhir `tests/fungsional.sh`; `$KUKI` berisi
sesi pengguna uji yang sudah masuk, jadi halaman `auth` bisa diuji)
```bash
# tests/kasus.sh: uji fungsional Kasus 0 (K-01 dst.)
t=$(token /pengumuman/kelola)
read -r k _ <<<"$(kirim /pengumuman --data-urlencode "_token=$t" \
  --data-urlencode "judul=Uji K-01" --data-urlencode "isi=dari tests/kasus.sh")"
cek K-01 302 "$k" "tulis pengumuman (pengguna masuk)"
cek K-02 200 "$(kode "$URL/pengumuman")" "halaman publik pengumuman"
n=$(curl -s -m 10 "$URL/api/pengumuman" | jq '.data | length')
cek K-03 ya "$([ "$n" -ge 1 ] && echo ya || echo tidak)" "API berisi $n pengumuman"
```

## 4. Menu dan dasbor

`resources/views/layouts/navigation.blade.php` memanggil
`@includeIf('kasus.menu', ['gaya' => 'desktop'])` dan `['gaya' => 'ponsel']`;
`dashboard.blade.php` memanggil `@includeIf('kasus.dasbor')`. Tanpa modul,
keduanya tidak menampilkan apa-apa.

## 5. Fitur starter yang dipakai ulang

| Kebutuhan modul | Pakai | Contoh di starter |
|-----------------|-------|-------------------|
| Login, pengguna | `auth` middleware, `$request->user()`, `foreignId('user_id')->constrained()` | `routes/web.php` |
| Unggah gambar/dokumen | disk `s3` + validasi isi berkas (`bail`, `file`, `max`, `mimes`, `mimetypes`), nama acak dari `store()`, tautan `URL::temporarySignedRoute` | `BerkasController` |
| Kerja di belakang layar (notifikasi, rekap) | job `implements ShouldQueue` + kunci unik (idempotensi); `KirimNotifikasi::dispatch(kunci, kepada, pesan)` bisa langsung dipakai | `app/Jobs/KirimNotifikasi.php` |
| Tugas terjadwal | `routes/kasus-jadwal.php`: `Schedule::call(...)->name('kasus:...')->cron(...)->onOneServer()` | `routes/console.php` |
| Database mati | tidak perlu kode tambahan: exception handler sudah menampilkan halaman ramah 503 (`api/*` = JSON 503) | `bootstrap/app.php`, `app/Support/DbGagal.php` |
| Teks Indonesia | tulis langsung di view; pesan validasi otomatis bahasa Indonesia (`lang/id`); nama kolom yang enak dibaca lewat argumen ketiga `validate()` | `lang/id/validation.php` |
| Identitas kelompok | `config('app.pemilik')` (env `APP_PEMILIK`) | `tamu/index.blade.php` |

## 6. Syarat lulus modul (sama dengan CI)

1. `php artisan test` lulus, termasuk `tests/Feature/Kasus/` (PostgreSQL).
2. Larastan level 5 tanpa error (`vendor/bin/phpstan analyse`; `app/`,
   `routes/`, `database/`, `config/` ikut dianalisis).
3. Semgrep `p/default` 0 temuan; `trivy fs --scanners secret` 0 temuan (tidak
   ada password, token, atau `APP_KEY` di berkas).
4. `bash tests/fungsional.sh` lulus, termasuk `tests/kasus.sh`.
5. Halaman grup `publik` tidak memakai `@csrf`, `auth()`, `session()`, atau
   `old()`; halaman setelah login tidak pernah di grup `publik`.
6. Teks yang dilihat pengguna dalam bahasa Indonesia.

## 7. Melepas modul

```bash
docker compose exec app php artisan migrate:rollback --step=JUMLAH_MIGRASI_MODUL --force
# hapus berkas yang dulu disalin (daftar = isi folder modul), lalu:
docker compose up -d --build --wait
```
