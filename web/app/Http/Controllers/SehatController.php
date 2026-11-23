<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Kontrak aplikasi kuliah (P8 probe, P10 slide 23):
 *   GET /healthz -> proses hidup, TIDAK menyentuh database (liveness)
 *   GET /health  -> cek database maks. 3 detik (readiness, uptime monitor)
 */
class SehatController extends Controller
{
    public function dangkal(): JsonResponse
    {
        return response()->json(['status' => 'ok']);
    }

    public function dalam(): JsonResponse
    {
        try {
            DB::select('SELECT 1');   // batas waktu koneksi 3 s (config/database.php)

            return response()->json(['status' => 'ok', 'db' => 'ok']);
        } catch (Throwable) {
            return response()->json(['status' => 'error', 'db' => 'gagal'], 503);
        }
    }
}
