<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Biasanya alamat URL (CSS, tautan, redirect) diambil dari header
        // proxy (TrustProxies, bootstrap/app.php). Bila proxy tidak
        // mengirimnya dan CSS tidak termuat, isi APP_URL dengan alamat
        // publik lalu PAKSA_APP_URL=1: semua URL dibuat dari APP_URL.
        if (config('app.paksa_url')) {
            $url = (string) config('app.url');
            URL::forceRootUrl($url);
            if (str_starts_with($url, 'https://')) {
                URL::forceScheme('https');
            }
        }
    }
}
