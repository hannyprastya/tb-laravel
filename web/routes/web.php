<?php

use App\Http\Controllers\BerkasController;
use App\Http\Controllers\BukuTamuController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// Rute halaman (grup 'web': sesi, cookie, CSRF). Berkas rute lain:
//   routes/publik.php  halaman baca-saja tanpa sesi, boleh di-cache
//   routes/api.php     /api/... JSON tanpa CSRF
//   routes/sehat.php   /health dan /healthz
//   routes/kasus.php   rute modul kasus, bila sudah dipasang (web/KASUS.md)

// Fitur contoh: buku tamu
Route::get('/', [BukuTamuController::class, 'index'])->name('tamu.index');
Route::post('/tamu', [BukuTamuController::class, 'simpan'])->name('tamu.simpan');

// Route::view (bukan closure) supaya bisa di-route:cache
Route::view('/dashboard', 'dashboard')->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Unggah berkas ke object storage (disk s3)
    Route::get('/berkas', [BerkasController::class, 'index'])->name('berkas.index');
    Route::post('/berkas', [BerkasController::class, 'simpan'])->name('berkas.simpan');
    Route::delete('/berkas/{berkas}', [BerkasController::class, 'hapus'])->name('berkas.hapus');
});

// Tautan unduh bertanda tangan + kedaluwarsa (seperti presigned URL)
Route::get('/berkas/{berkas}/unduh', [BerkasController::class, 'unduh'])
    ->middleware('signed')->name('berkas.unduh');

require __DIR__.'/auth.php';
