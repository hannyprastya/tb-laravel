<?php

namespace Tests\Feature;

use App\Jobs\KirimNotifikasi;
use App\Models\Tamu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/** Fitur contoh buku tamu (padanan F-01..F-05 buku tamu lama). */
class BukuTamuTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_utama_menampilkan_pemilik_dan_tamu(): void
    {
        Tamu::factory()->create(['nama' => 'Ani']);

        $this->get('/')->assertOk()->assertSee('Kelompok Uji')->assertSee('Ani');
    }

    public function test_simpan_nama_lalu_titip_notifikasi(): void
    {
        Queue::fake();

        $this->post('/tamu', ['nama' => 'Budi'])->assertRedirect('/');

        $tamu = Tamu::query()->where('nama', 'Budi')->firstOrFail();
        Queue::assertPushed(KirimNotifikasi::class,
            fn (KirimNotifikasi $job) => $job->kunci === 'tamu-'.$tamu->id);
    }

    public function test_nama_kosong_ditolak(): void
    {
        $this->post('/tamu', ['nama' => ''])->assertSessionHasErrors('nama');
        $this->assertDatabaseCount('tamu', 0);
    }

    public function test_nama_berupa_array_ditolak(): void
    {
        $this->post('/tamu', ['nama' => ['x']])->assertSessionHasErrors('nama');
    }

    public function test_nama_lebih_dari_80_karakter_ditolak(): void
    {
        $this->post('/tamu', ['nama' => str_repeat('a', 81)])->assertSessionHasErrors('nama');
    }

    public function test_halaman_publik_boleh_dicache_dan_tanpa_cookie(): void
    {
        Tamu::factory()->create(['nama' => 'Citra']);

        $r = $this->get('/tamu')->assertOk()->assertSee('Citra');
        $cc = (string) $r->headers->get('Cache-Control');
        $this->assertStringContainsString('public', $cc);
        $this->assertStringContainsString('max-age=60', $cc);
        $this->assertSame([], $r->headers->getCookies());
    }

    public function test_halaman_web_tidak_boleh_dicache(): void
    {
        $cc = (string) $this->get('/')->headers->get('Cache-Control');
        $this->assertStringContainsString('private', $cc);
    }

    public function test_api_simpan_dan_status_notifikasi(): void
    {
        Queue::fake();

        $id = $this->postJson('/api/tamu', ['nama' => 'Dewi'])
            ->assertCreated()->assertJsonPath('nama', 'Dewi')->json('id');

        $this->getJson("/api/tamu/$id")->assertOk()
            ->assertJsonPath('notifikasi_terkirim', false);

        (new KirimNotifikasi("tamu-$id", 'Dewi', 'halo'))->handle();

        $this->getJson("/api/tamu/$id")->assertJsonPath('notifikasi_terkirim', true);
        $this->getJson('/api/tamu')->assertOk()->assertJsonPath('data.0.nama', 'Dewi');
    }

    public function test_api_validasi_422(): void
    {
        $this->postJson('/api/tamu', ['nama' => ''])->assertStatus(422)
            ->assertJsonValidationErrors('nama');
    }

    public function test_alamat_tidak_ada_404(): void
    {
        $this->get('/tidak-ada')->assertNotFound();
    }
}
