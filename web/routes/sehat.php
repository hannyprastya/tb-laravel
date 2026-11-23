<?php

use App\Http\Controllers\SehatController;
use Illuminate\Support\Facades\Route;

// Dimuat tanpa grup middleware (bootstrap/app.php): tanpa sesi, tanpa
// cookie, sehingga /healthz tetap 200 walau database mati.
Route::get('/healthz', [SehatController::class, 'dangkal']);
Route::get('/health', [SehatController::class, 'dalam']);
