<?php

// Dijalankan PHPUnit sebelum semua uji (phpunit.xml, atribut bootstrap):
// membuat database uji (mis. "uji") bila belum ada, supaya
// "docker compose run --rm uji" langsung jalan tanpa langkah manual.
// Database "app" milik aplikasi yang sedang jalan tidak pernah disentuh.

require __DIR__.'/../vendor/autoload.php';

$url = parse_url((string) getenv('DATABASE_URL'));
$nama = ltrim($url['path'] ?? '', '/');
if ($nama !== '' && isset($url['host'])) {
    $dsn = fn (string $db) => sprintf('pgsql:host=%s;port=%d;dbname=%s',
        $url['host'], $url['port'] ?? 5432, $db);
    $user = urldecode($url['user'] ?? '');
    $pass = urldecode($url['pass'] ?? '');
    try {
        new PDO($dsn($nama), $user, $pass);
    } catch (PDOException $e) {
        if (str_contains($e->getMessage(), 'does not exist')) {
            (new PDO($dsn('postgres'), $user, $pass))
                ->exec('CREATE DATABASE "'.str_replace('"', '', $nama).'"');
            fwrite(STDERR, "database $nama dibuat untuk uji\n");
        }
    }
}
