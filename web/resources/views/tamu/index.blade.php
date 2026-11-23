{{-- Buku tamu (GET /): daftar 20 tamu terakhir + formulir. --}}
<x-publik-layout judul="Buku tamu">
    <h1 class="text-2xl font-semibold">Buku tamu {{ config('app.pemilik') }}</h1>
    <p class="text-sm text-gray-500">server {{ gethostname() }}</p>

    @if (session('status'))
        <p class="mt-4 rounded bg-green-50 p-3 text-green-800">{{ session('status') }}</p>
    @endif

    <form method="POST" action="{{ route('tamu.simpan') }}" class="mt-6 flex flex-wrap items-start gap-2">
        @csrf
        <div class="grow">
            <x-input-label for="nama" value="Nama" class="sr-only" />
            <x-text-input id="nama" name="nama" :value="old('nama')" required maxlength="80"
                placeholder="Nama Anda" class="w-full" />
            <x-input-error :messages="$errors->get('nama')" class="mt-1" />
        </div>
        <x-primary-button>Simpan</x-primary-button>
    </form>

    <ul class="mt-6 divide-y divide-gray-200 rounded bg-white shadow-sm">
        @forelse ($tamu as $t)
            <li class="px-4 py-2">{{ $t->nama }} <span class="text-sm text-gray-500">· {{ $t->created_at->format('d-m H:i') }}</span></li>
        @empty
            <li class="px-4 py-2 text-gray-500">Belum ada tamu.</li>
        @endforelse
    </ul>
</x-publik-layout>
