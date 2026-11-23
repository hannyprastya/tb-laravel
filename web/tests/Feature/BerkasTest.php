<?php

namespace Tests\Feature;

use App\Models\Berkas;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/** Unggah ke disk s3 (di uji diganti disk palsu lokal oleh Storage::fake). */
class BerkasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('s3');
    }

    /**
     * Berkas unggahan SUNGGUHAN dari isi tertentu. UploadedFile::fake() tidak
     * dipakai karena jenisnya ditebak dari nama berkas, bukan dari isinya.
     */
    private function unggahan(string $nama, string $isi): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'uji');
        file_put_contents($path, $isi);

        return new UploadedFile($path, $nama, null, null, true);
    }

    /** PNG 1x1 piksel sungguhan (tanpa butuh ekstensi GD). */
    private function png(string $nama, int $tambahan = 0): UploadedFile
    {
        $isi = (string) base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJ'
            .'AAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');

        return $this->unggahan($nama, $isi.str_repeat("\0", $tambahan));
    }

    public function test_tamu_belum_masuk_diarahkan_ke_login(): void
    {
        $this->get('/berkas')->assertRedirect('/login');
    }

    public function test_unggah_gambar_disimpan_dengan_nama_acak(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/berkas', [
            'berkas' => $this->png('../../etc/foto saya.png'),
        ])->assertRedirect('/berkas')->assertSessionHasNoErrors();

        $b = Berkas::query()->firstOrFail();
        Storage::disk('s3')->assertExists($b->path);
        $this->assertSame('foto saya.png', $b->nama_tampil);   // tanpa ../
        $this->assertStringStartsWith("unggahan/{$user->id}/", $b->path);
        $this->assertStringNotContainsString('foto', $b->path);
        $this->assertStringEndsWith('.png', $b->path);

        $this->actingAs($user)->get('/berkas')->assertOk()->assertSee('foto saya.png');
    }

    public function test_berkas_php_berkedok_png_ditolak(): void
    {
        $user = User::factory()->create();
        $palsu = $this->unggahan('jahat.png', '<?php system($_GET["c"]);');

        $this->actingAs($user)->post('/berkas', ['berkas' => $palsu])
            ->assertSessionHasErrors('berkas');
        $this->assertDatabaseCount('berkas', 0);
    }

    public function test_berkas_lebih_dari_2_mb_ditolak(): void
    {
        $user = User::factory()->create();
        $besar = $this->png('besar.png', 3 * 1024 * 1024);   // PNG sah, 3 MB

        $this->actingAs($user)->post('/berkas', ['berkas' => $besar])
            ->assertSessionHasErrors('berkas');
    }

    public function test_tautan_bertanda_tangan(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post('/berkas', [
            'berkas' => $this->png('a.png'),
        ]);
        $b = Berkas::query()->firstOrFail();
        $url = URL::temporarySignedRoute('berkas.unduh', now()->addMinutes(5), ['berkas' => $b->id]);

        $this->get($url)->assertOk()->assertDownload('a.png');
        $this->get($url.'x')->assertForbidden();                   // tanda tangan diubah
        $this->get("/berkas/{$b->id}/unduh")->assertForbidden();   // tanpa tanda tangan

        $this->travel(6)->minutes();
        $this->get($url)->assertForbidden();                       // kedaluwarsa
    }

    public function test_hapus_hanya_oleh_pemilik(): void
    {
        $pemilik = User::factory()->create();
        $lain = User::factory()->create();
        $this->actingAs($pemilik)->post('/berkas', [
            'berkas' => $this->png('a.png'),
        ]);
        $b = Berkas::query()->firstOrFail();

        $this->actingAs($lain)->delete("/berkas/{$b->id}")->assertNotFound();
        $this->actingAs($pemilik)->delete("/berkas/{$b->id}")->assertRedirect('/berkas');
        Storage::disk('s3')->assertMissing($b->path);
    }
}
