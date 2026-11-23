<?php

use App\Http\Middleware\CachePublik;
use App\Support\DbGagal;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',      // halaman (sesi + CSRF)
        api: __DIR__.'/../routes/api.php',      // /api/... JSON tanpa CSRF
        commands: __DIR__.'/../routes/console.php',
        then: function () {
            // /health dan /healthz tanpa middleware apa pun: tidak membuka
            // sesi, jadi /healthz tidak pernah menyentuh database.
            Route::group([], base_path('routes/sehat.php'));

            // Halaman baca-saja untuk pengunjung anonim (grup 'publik').
            Route::middleware('publik')->group(base_path('routes/publik.php'));

            // Slot modul kasus (web/KASUS.md): dimuat hanya bila berkasnya
            // ada. Berkas itu sendiri memilih grup 'web', 'publik', atau
            // 'api' untuk setiap rutenya.
            if (is_file(base_path('routes/kasus.php'))) {
                Route::group([], base_path('routes/kasus.php'));
            }
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Render, Codespaces, dan Nginx LB meneruskan permintaan lewat proxy.
        // Tanpa ini Laravel mengira semua permintaan http (bukan https) dan
        // membuat URL yang salah. Di produksi aplikasi hanya bisa dihubungi
        // lewat proxy itu, jadi semua alamat proxy ('*') dipercaya.
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO,
        );

        // Grup 'publik': halaman baca-saja untuk pengunjung anonim. Tanpa
        // sesi dan tanpa cookie, diberi Cache-Control public, sehingga boleh
        // disimpan cache Nginx (P13). Jangan pakai @csrf atau auth() di sini.
        $middleware->group('publik', [
            SubstituteBindings::class,
            CachePublik::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // P15 (graceful degradation): database mati atau tidak menjawab
        // dalam 3 detik -> halaman ramah 503 + Retry-After, bukan error 500.
        // Status tetap 5xx supaya alert P11 menyala.
        $exceptions->report(function (Throwable $e) {
            if (DbGagal::cek($e)) {
                Log::error('DB gagal: '.DbGagal::pesan($e));

                return false;   // cukup satu baris log, tanpa stack trace
            }
        });

        $exceptions->render(function (Throwable $e, Request $request) {
            if (! DbGagal::cek($e)) {
                return null;    // error lain ditangani Laravel seperti biasa
            }
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['status' => 'error', 'db' => 'gagal'], 503)
                    ->header('Retry-After', '60');
            }

            return response()->view('errors.db-gagal', [], 503)
                ->header('Retry-After', '60');
        });
    })->create();
