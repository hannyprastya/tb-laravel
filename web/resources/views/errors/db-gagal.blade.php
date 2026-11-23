{{--
    P15 (graceful degradation): ditampilkan exception handler
    (bootstrap/app.php) saat database tidak bisa dihubungi. Status 503 +
    Retry-After: 60. Sengaja berdiri sendiri: tanpa sesi, tanpa login, tanpa
    database, tanpa berkas CSS hasil build.
--}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sedang gangguan · {{ config('app.pemilik') }}</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #f3f4f6; color: #111827; margin: 0; }
        main { max-width: 36rem; margin: 15vh auto; padding: 2rem; background: #fff; border-radius: .5rem; }
        h1 { font-size: 1.5rem; margin: 0 0 1rem; }
        p.kecil { color: #6b7280; font-size: .875rem; }
    </style>
</head>
<body>
    <main>
        <h1>{{ config('app.pemilik') }}</h1>
        <p>Maaf, aplikasi sedang gangguan: database tidak bisa dihubungi.
           Coba lagi 1 menit lagi.</p>
        <p class="kecil">Kode 503 · Data yang sudah tersimpan tidak hilang.</p>
    </main>
</body>
</html>
