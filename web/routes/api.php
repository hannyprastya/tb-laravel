<?php

use App\Http\Controllers\Api\TamuController;
use Illuminate\Support\Facades\Route;

// Rute JSON, otomatis berawalan /api dan memakai grup 'api': tanpa sesi,
// tanpa cookie, tanpa CSRF. Cocok untuk curl, k6, dan uji fungsional.
Route::get('/tamu', [TamuController::class, 'index']);
Route::post('/tamu', [TamuController::class, 'simpan']);
Route::get('/tamu/{tamu}', [TamuController::class, 'tampil']);
