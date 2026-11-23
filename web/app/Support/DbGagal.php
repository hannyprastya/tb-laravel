<?php

namespace App\Support;

use Illuminate\Database\LostConnectionDetector;
use Illuminate\Database\QueryException;
use PDOException;
use Throwable;

/**
 * Mengenali exception yang berarti "database tidak bisa dihubungi"
 * (mati, salah alamat, timeout 3 detik, koneksi putus).
 *
 * Hanya error koneksi yang dijadikan halaman ramah 503. Error SQL biasa
 * (salah nama kolom, data dobel) tetap 500 karena itu bug kode, bukan
 * gangguan database.
 */
class DbGagal
{
    public static function cek(Throwable $e): bool
    {
        if (! $e instanceof QueryException && ! $e instanceof PDOException) {
            return false;
        }

        // SQLSTATE kelas 08 = connection exception; 57P01-57P03 = server
        // dimatikan atau sedang menyala ulang.
        $kode = (string) $e->getCode();
        if (str_starts_with($kode, '08') || in_array($kode, ['57P01', '57P02', '57P03'], true)) {
            return true;
        }

        return (new LostConnectionDetector)->causedByLostConnection($e);
    }

    /**
     * Pesan untuk log: hanya pesan driver PostgreSQL, TANPA teks SQL dan
     * nilai parameternya (bisa berisi ID sesi atau data pengguna).
     */
    public static function pesan(Throwable $e): string
    {
        $asal = $e instanceof QueryException ? ($e->getPrevious() ?? $e) : $e;

        return trim(strtok($asal->getMessage(), "\n") ?: $asal->getMessage());
    }
}
