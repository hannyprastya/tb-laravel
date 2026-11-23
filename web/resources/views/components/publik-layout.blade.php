{{--
    Tata letak halaman untuk semua pengunjung (sudah masuk atau belum).
    :statis="true" untuk halaman grup 'publik' (tanpa sesi): menu tidak
    memeriksa status masuk, sehingga halaman sama untuk semua orang.
--}}
@props(['judul' => null, 'statis' => false])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $judul ? $judul.' · ' : '' }}{{ config('app.pemilik') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-100 text-gray-900">
        <header class="bg-white border-b border-gray-200">
            <div class="max-w-3xl mx-auto px-4 h-14 flex items-center justify-between">
                <a href="{{ route('tamu.index') }}" class="font-semibold">{{ config('app.pemilik') }}</a>
                <nav class="flex gap-4 text-sm">
                    @if ($statis)
                        <a href="{{ route('tamu.index') }}" class="text-gray-600 hover:text-gray-900">Tulis di buku tamu</a>
                        <a href="{{ route('login') }}" class="text-gray-600 hover:text-gray-900">{{ __('Log in') }}</a>
                    @else
                        @auth
                            <a href="{{ route('dashboard') }}" class="text-gray-600 hover:text-gray-900">{{ __('Dashboard') }}</a>
                            <a href="{{ route('berkas.index') }}" class="text-gray-600 hover:text-gray-900">Berkas</a>
                        @else
                            <a href="{{ route('login') }}" class="text-gray-600 hover:text-gray-900">{{ __('Log in') }}</a>
                            <a href="{{ route('register') }}" class="text-gray-600 hover:text-gray-900">{{ __('Register') }}</a>
                        @endauth
                    @endif
                </nav>
            </div>
        </header>
        <main class="max-w-3xl mx-auto px-4 py-8">
            {{ $slot }}
        </main>
    </body>
</html>
