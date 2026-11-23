<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\KirimNotifikasi;
use App\Models\Tamu;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * API JSON buku tamu (/api/tamu): tanpa sesi dan tanpa CSRF, untuk curl,
 * k6, dan uji fungsional. Validasi gagal -> 422 JSON.
 */
class TamuController extends Controller
{
    public function index(): JsonResponse
    {
        $tamu = Tamu::query()->latest('id')->limit(20)->get(['id', 'nama', 'created_at']);

        return response()->json(['data' => $tamu]);
    }

    public function simpan(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:80'],
        ]);
        $tamu = Tamu::create(['nama' => $data['nama']]);
        KirimNotifikasi::dispatch('tamu-'.$tamu->id, $tamu->nama,
            'Terima kasih sudah mengisi buku tamu '.config('app.pemilik').'.');

        return response()->json(['id' => $tamu->id, 'nama' => $tamu->nama], 201);
    }

    /** notifikasi_terkirim = true setelah worker menjalankan job-nya. */
    public function tampil(Tamu $tamu): JsonResponse
    {
        return response()->json([
            'id' => $tamu->id,
            'nama' => $tamu->nama,
            'waktu' => $tamu->created_at,
            'notifikasi_terkirim' => DB::table('notifikasi')
                ->where('kunci', 'tamu-'.$tamu->id)->exists(),
        ]);
    }
}
