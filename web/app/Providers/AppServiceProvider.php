<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
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
        // Tautan reset password selalu dibuat dari APP_URL, bukan dari header
        // Host/X-Forwarded-Host permintaan: header itu bisa dipalsukan, dan
        // tautan berisi token yang tidak boleh terkirim ke domain lain.
        ResetPassword::createUrlUsing(fn ($user, string $token): string => rtrim((string) config('app.url'), '/')
            .'/reset-password/'.$token.'?email='.urlencode($user->getEmailForPasswordReset()));

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
