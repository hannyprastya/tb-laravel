<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/** P15: database mati -> halaman ramah 503 + Retry-After, dicatat di log. */
class DbGagalTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.connections.pgsql.url' => 'postgresql://app:x@127.0.0.1:1/tidakada']);
        DB::purge('pgsql');
    }

    public function test_halaman_utama_503_ramah(): void
    {
        Log::spy();

        $this->get('/')
            ->assertStatus(503)
            ->assertHeader('Retry-After', '60')
            ->assertSee('Maaf, aplikasi sedang gangguan')
            ->assertSee('Kelompok Uji');

        Log::shouldHaveReceived('error')
            ->withArgs(fn ($pesan) => str_starts_with($pesan, 'DB gagal: '))
            ->once();
    }

    public function test_halaman_publik_503_ramah(): void
    {
        $this->get('/tamu')->assertStatus(503)->assertSee('Maaf');
    }

    public function test_api_503_json(): void
    {
        $this->getJson('/api/tamu')
            ->assertStatus(503)
            ->assertHeader('Retry-After', '60')
            ->assertExactJson(['status' => 'error', 'db' => 'gagal']);
    }
}
