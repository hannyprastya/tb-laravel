<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Untuk grup rute 'publik' (bootstrap/app.php): jawaban GET yang sukses
 * diberi "Cache-Control: public, max-age=60", artinya sama untuk semua
 * pengunjung dan boleh disimpan cache (Nginx P13, CDN) paling lama 60 detik.
 * Rute lain tetap "no-cache, private" (bawaan Laravel), jadi tidak di-cache.
 */
class CachePublik
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->isMethodCacheable() && $response->getStatusCode() === 200
            && ! $response->headers->has('Set-Cookie')) {
            $response->setPublic();
            $response->setMaxAge((int) config('app.cache_publik_detik', 60));
        }

        return $response;
    }
}
