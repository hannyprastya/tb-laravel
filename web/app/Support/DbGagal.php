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

        // Database macet (docker compose pause db, timeout 3 s): pdo_pgsql
        // memberi kode driver 7, bukan SQLSTATE, walau pesannya
        // "SQLSTATE[08006] [7] ... timeout expired". SQLSTATE dibaca juga
        // dari pesan supaya rute biasa tetap 503, bukan 500. Yang dibaca
        // hanya pesan driver dan hanya di awalnya: pesan QueryException
        // memuat teks SQL beserta nilai dari pengguna, jadi input berisi
        // "SQLSTATE[08006]" tidak boleh mengubah error SQL biasa menjadi 503.
        $asal = $e instanceof QueryException ? ($e->getPrevious() ?? $e) : $e;
        if (preg_match('/^SQLSTATE\[(08\w{3}|57P0[123])\]/', $asal->getMessage()) === 1) {
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
