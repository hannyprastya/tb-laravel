<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 text-gray-900">
                Halo, {{ Auth::user()->name }}. {{ __("You're logged in!") }}
                <p class="mt-1 text-sm text-gray-500">{{ config('app.pemilik') }}</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <a href="{{ route('tamu.index') }}" class="block bg-white shadow-sm sm:rounded-lg p-6 hover:bg-gray-50">
                    <h3 class="font-semibold">Buku tamu</h3>
                    <p class="text-sm text-gray-600">Fitur contoh starter: daftar dan formulir tamu.</p>
                </a>
                <a href="{{ route('berkas.index') }}" class="block bg-white shadow-sm sm:rounded-lg p-6 hover:bg-gray-50">
                    <h3 class="font-semibold">Berkas</h3>
                    <p class="text-sm text-gray-600">Unggah gambar atau PDF ke object storage.</p>
                </a>
                {{-- Kartu modul kasus (web/KASUS.md): resources/views/kasus/dasbor.blade.php --}}
                @includeIf('kasus.dasbor')
            </div>
        </div>
    </div>
</x-app-layout>
