{{--
    GET /tamu (grup 'publik'): daftar yang sama dengan halaman utama, tanpa
    formulir, tanpa sesi, tanpa @csrf, sehingga sama untuk semua pengunjung
    dan boleh di-cache. Waktu dibuat di bawah menunjukkan kapan halaman ini
    dibuat server (dari cache = jam lama).
--}}
<x-publik-layout judul="Daftar tamu" :statis="true">
    <h1 class="text-2xl font-semibold">Daftar tamu {{ config('app.pemilik') }}</h1>
    <p class="text-sm text-gray-500">dibuat {{ now()->format('H:i:s') }} oleh server {{ gethostname() }}</p>

    <ul class="mt-6 divide-y divide-gray-200 rounded bg-white shadow-sm">
        @forelse ($tamu as $t)
            <li class="px-4 py-2">{{ $t->nama }} <span class="text-sm text-gray-500">· {{ $t->created_at->format('d-m H:i') }}</span></li>
        @empty
            <li class="px-4 py-2 text-gray-500">Belum ada tamu.</li>
        @endforelse
    </ul>
</x-publik-layout>
