<?php

namespace App\Http\Controllers;

use App\Models\Berkas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Unggah berkas ke object storage (disk s3: Floci di compose, Supabase
 * Storage atau S3 lain di produksi). Hanya untuk pengguna yang sudah masuk.
 */
class BerkasController extends Controller
{
    /** Tautan unduh berlaku 5 menit, seperti presigned URL (P4). */
    private const MENIT_TAUTAN = 5;

    public function index(Request $request): View
    {
        $berkas = Berkas::query()->where('user_id', $request->user()?->id)
            ->latest('id')->get();

        return view('berkas.index', [
            'berkas' => $berkas,
            'tautan' => $berkas->mapWithKeys(fn (Berkas $b) => [
                $b->id => URL::temporarySignedRoute('berkas.unduh',
                    now()->addMinutes(self::MENIT_TAUTAN), ['berkas' => $b->id]),
            ]),
            'menit' => self::MENIT_TAUTAN,
        ]);
    }

    public function simpan(Request $request): RedirectResponse
    {
        // Jenis berkas diperiksa dari ISINYA (mimes/mimetypes membaca byte
        // berkas), bukan dari nama atau ekstensi kiriman browser. Maks. 2 MB.
        // bail = berhenti di aturan pertama yang gagal (satu pesan saja).
        $request->validate([
            'berkas' => ['bail', 'required', 'file', 'max:2048',
                'mimes:jpg,jpeg,png,pdf',
                'mimetypes:image/jpeg,image/png,application/pdf'],
        ]);
        $file = $request->file('berkas');
        $user = $request->user();
        abort_if($file === null || $user === null, 400);

        // Nama di storage dibuat server: 40 karakter acak + ekstensi dari
        // isi berkas. Nama asli kiriman klien TIDAK pernah dipakai sebagai
        // path (cegah ../, nama dobel, atau .php).
        $path = $file->store('unggahan/'.$user->id, 's3');
        abort_if($path === false, 500, 'Gagal menyimpan berkas');

        Berkas::create([
            'user_id' => $user->id,
            'nama_tampil' => $this->namaTampil($file->getClientOriginalName()),
            'path' => $path,
            'mime' => (string) $file->getMimeType(),
            'ukuran' => (int) $file->getSize(),
        ]);

        return redirect()->route('berkas.index')->with('status', 'Berkas tersimpan.');
    }

    /**
     * GET /berkas/{id}/unduh?expires=...&signature=... (middleware 'signed').
     * Siapa pun yang memegang tautan bisa mengunduh sampai kedaluwarsa, sama
     * seperti presigned URL S3; tautan hanya dibuat untuk pemilik berkas.
     * Tanda tangan diubah satu huruf -> 403.
     */
    public function unduh(Berkas $berkas): StreamedResponse
    {
        return Storage::disk('s3')->download($berkas->path, $berkas->nama_tampil, [
            'Content-Type' => $berkas->mime,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function hapus(Request $request, Berkas $berkas): RedirectResponse
    {
        abort_unless($berkas->user_id === $request->user()?->id, 404);
        Storage::disk('s3')->delete($berkas->path);
        $berkas->delete();

        return redirect()->route('berkas.index')->with('status', 'Berkas dihapus.');
    }

    /** Nama asli hanya untuk ditampilkan: tanpa folder, tanpa karakter kontrol. */
    private function namaTampil(string $asli): string
    {
        $nama = preg_replace('/[\x00-\x1F\x7F]/u', '', basename(str_replace('\\', '/', $asli))) ?? '';

        return Str::limit($nama !== '' ? $nama : 'berkas', 120, '');
    }
}
