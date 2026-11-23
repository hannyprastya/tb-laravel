<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Kontrak /health dan /healthz (P10 slide 23). */
class SehatTest extends TestCase
{
    public function test_healthz_200_tanpa_database(): void
    {
        $this->get('/healthz')->assertOk()->assertExactJson(['status' => 'ok']);
    }

    public function test_health_200_saat_database_normal(): void
    {
        $this->get('/health')->assertOk()->assertExactJson(['status' => 'ok', 'db' => 'ok']);
    }

    public function test_health_503_healthz_200_saat_database_mati(): void
    {
        $this->putuskanDatabase();

        $this->get('/health')->assertStatus(503)
            ->assertExactJson(['status' => 'error', 'db' => 'gagal']);
        $this->get('/healthz')->assertOk();
    }

    public function test_health_tanpa_cookie_sesi(): void
    {
        $this->get('/health')->assertOk()->assertCookieMissing('tb_sesi');
    }

    /** Simulasi database mati: arahkan koneksi ke port yang tidak dipakai. */
    private function putuskanDatabase(): void
    {
        config(['database.connections.pgsql.url' => 'postgresql://app:x@127.0.0.1:1/tidakada']);
        DB::purge('pgsql');
    }
}
