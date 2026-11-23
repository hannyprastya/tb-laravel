<?php

use App\Http\Controllers\BukuTamuController;
use Illuminate\Support\Facades\Route;

// Grup 'publik' (bootstrap/app.php): tanpa sesi, tanpa cookie, tanpa CSRF,
// jawaban diberi Cache-Control public. Hanya untuk halaman GET yang sama
// bagi semua pengunjung. Formulir dan halaman pengguna masuk TIDAK di sini.
Route::get('/tamu', [BukuTamuController::class, 'publik'])->name('tamu.publik');
