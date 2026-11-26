<?php

namespace Tests\Unit;

use App\Support\DbGagal;
use Illuminate\Database\QueryException;
use PDOException;
use PHPUnit\Framework\TestCase;

/**
 * P15: database macet (timeout 3 s) harus dikenali sebagai DB gagal walau
 * pdo_pgsql memberi kode driver 7, bukan SQLSTATE (docker compose pause db).
 */
class DbGagalMacetTest extends TestCase
{
    public function test_timeout_dengan_kode_driver_dikenali(): void
    {
        $pdo = new class('SQLSTATE[08006] [7] connection to server at "db" (10.0.0.2), port 5432 failed: timeout expired') extends PDOException
        {
            public function __construct(string $pesan)
            {
                parent::__construct($pesan);
                $this->code = 7;
            }
        };
        $e = new QueryException('pgsql', 'select 1', [], $pdo);

        $this->assertSame(7, $e->getCode());
        $this->assertTrue(DbGagal::cek($e));
    }

    public function test_sqlstate_lain_di_pesan_tetap_bukan_db_gagal(): void
    {
        $pdo = new class('SQLSTATE[42703]: Undefined column: kolom "x" tidak ada') extends PDOException
        {
            public function __construct(string $pesan)
            {
                parent::__construct($pesan);
                $this->code = '42703';
            }
        };

        $this->assertFalse(DbGagal::cek(new QueryException('pgsql', 'select x', [], $pdo)));
    }

    public function test_teks_sqlstate_di_nilai_pengguna_tidak_menjadi_db_gagal(): void
    {
        // Error SQL biasa (22P02) dengan nilai pengguna berisi "SQLSTATE[08006]":
        // tetap bug/500, bukan 503 "database gangguan".
        $pdo = new class('SQLSTATE[22P02]: Invalid text representation: invalid input syntax for type integer') extends PDOException
        {
            public function __construct(string $pesan)
            {
                parent::__construct($pesan);
                $this->code = '22P02';
            }
        };
        $e = new QueryException('pgsql', 'select * from buku where id = ?', ['SQLSTATE[08006] [7] x'], $pdo);

        $this->assertStringContainsString('SQLSTATE[08006]', $e->getMessage());
        $this->assertFalse(DbGagal::cek($e));
    }
}
