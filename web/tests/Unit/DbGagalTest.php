<?php

namespace Tests\Unit;

use App\Support\DbGagal;
use Illuminate\Database\QueryException;
use PDOException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/** Hanya error koneksi yang menjadi halaman 503; bug SQL tetap 500. */
class DbGagalTest extends TestCase
{
    public function test_koneksi_gagal_dikenali(): void
    {
        $this->assertTrue(DbGagal::cek($this->galat('08006', 'connection refused')));
        $this->assertTrue(DbGagal::cek($this->galat('57P01', 'terminating connection')));
    }

    public function test_error_sql_biasa_bukan_db_gagal(): void
    {
        $this->assertFalse(DbGagal::cek($this->galat('42703', 'column "x" does not exist')));
        $this->assertFalse(DbGagal::cek(new RuntimeException('lain')));
    }

    public function test_pesan_log_tanpa_sql_dan_parameter(): void
    {
        $pesan = DbGagal::pesan($this->galat('08006', 'could not connect'));

        $this->assertSame('SQLSTATE[08006]: could not connect', $pesan);
    }

    /** QueryException dengan SQLSTATE tertentu, seperti buatan pdo_pgsql. */
    private function galat(string $kode, string $pesan): QueryException
    {
        $pdo = new class("SQLSTATE[$kode]: $pesan", $kode) extends PDOException
        {
            public function __construct(string $pesan, string $kode)
            {
                parent::__construct($pesan);
                $this->code = $kode;
            }
        };

        return new QueryException('pgsql', 'select 1', [], $pdo);
    }
}
