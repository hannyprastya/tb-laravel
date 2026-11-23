<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Berkas saya</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <p class="rounded bg-green-50 p-3 text-green-800">{{ session('status') }}</p>
            @endif

            <form method="POST" action="{{ route('berkas.simpan') }}" enctype="multipart/form-data"
                  class="bg-white shadow-sm sm:rounded-lg p-6 space-y-3">
                @csrf
                <x-input-label for="berkas" value="Pilih berkas (JPG, PNG, atau PDF, maks. 2 MB)" />
                <input id="berkas" name="berkas" type="file" required accept=".jpg,.jpeg,.png,.pdf"
                       class="block w-full text-sm">
                <x-input-error :messages="$errors->get('berkas')" />
                <x-primary-button>Unggah</x-primary-button>
            </form>

            <div class="bg-white shadow-sm sm:rounded-lg">
                <table class="w-full text-sm">
                    <thead class="text-left text-gray-500">
                        <tr><th class="p-3">Nama</th><th class="p-3">Ukuran</th><th class="p-3">Diunggah</th><th class="p-3"></th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($berkas as $b)
                            <tr>
                                <td class="p-3 break-all">{{ $b->nama_tampil }}</td>
                                <td class="p-3">{{ number_format($b->ukuran / 1024, 1, ',', '.') }} KB</td>
                                <td class="p-3">{{ $b->created_at->format('d-m-Y H:i') }}</td>
                                <td class="p-3 flex gap-3">
                                    <a href="{{ $tautan[$b->id] }}" class="text-indigo-600 underline">Unduh</a>
                                    <form method="POST" action="{{ route('berkas.hapus', $b) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-red-600 underline">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="p-3 text-gray-500">Belum ada berkas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <p class="text-sm text-gray-500">Tautan unduh berlaku {{ $menit }} menit; muat ulang
                halaman untuk tautan baru. Isi berkas disimpan di object storage, bukan di container.</p>
        </div>
    </div>
</x-app-layout>
