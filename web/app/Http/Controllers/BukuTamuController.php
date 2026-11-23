<?php

namespace App\Http\Controllers;

use App\Jobs\KirimNotifikasi;
use App\Models\Tamu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Buku tamu: fitur contoh starter (pengganti index.php P5-P11), supaya
 * aplikasi bisa dijalankan dan diuji sebelum modul kasus dipasang.
 */
class BukuTamuController extends Controller
{
    /** GET / : daftar 20 tamu terakhir + formulir (grup web, pakai sesi). */
    public function index(): View
    {
        return view('tamu.index', [
            'tamu' => Tamu::query()->latest('id')->limit(20)->get(),
        ]);
    }

    /** GET /tamu : daftar yang sama, baca-saja, boleh di-cache (grup publik). */
    public function publik(): View
    {
        return view('tamu.publik', [
            'tamu' => Tamu::query()->latest('id')->limit(20)->get(),
        ]);
    }

    /** POST /tamu : simpan nama, lalu titip notifikasi ke antrean. */
    public function simpan(Request $request): RedirectResponse
    {
        // 'string' menolak nama berupa array (F-04 buku tamu lama)
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:80'],
        ]);

        $tamu = Tamu::create(['nama' => $data['nama']]);

        // Dikerjakan worker (php artisan queue:work), bukan di request ini.
        KirimNotifikasi::dispatch('tamu-'.$tamu->id, $tamu->nama,
            'Terima kasih sudah mengisi buku tamu '.config('app.pemilik').'.');

        return redirect('/')->with('status', 'Terima kasih, '.$tamu->nama.'!');
    }
}
