<x-card title="Data Barang">
    <div class="p-6">
        {{-- Header --}}
        <div class="flex justify-between mb-4">
            <input type="text" wire:model.live="search" placeholder="Cari barang..."
                class="border px-4 py-2 rounded w-1/3 dark:bg-zinc-800 dark:text-white">
            <button wire:click="openModal" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                Tambah Barang
            </button>
        </div>

        {{-- Tabel --}}
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white dark:bg-zinc-800 text-sm">
                <thead class="bg-gray-100 dark:bg-zinc-700 text-gray-700 dark:text-gray-300">
                    <tr>
                        <th class="p-2 text-left">Gambar</th>
                        <th class="p-2 text-left">Kode</th>
                        <th class="p-2 text-left">Nama</th>
                        <th class="p-2 text-left">Satuan</th>
                        <th class="p-2 text-left">Merek</th>
                        <th class="p-2 text-left">Stock Minim</th>
                        <th class="p-2 text-left">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr class="border-b dark:border-zinc-700">
                            <td class="p-2">
                                @if ($item->img)
                                    <img src="{{ asset('storage/' . $item->img) }}"
                                        class="w-12 h-12 object-cover rounded">
                                @else
                                    {{-- Atau gunakan gambar default --}}
                                    <img src="{{ asset('img/photo.png') }}" class="w-12 h-12 object-cover rounded">
                                @endif
                            </td>
                            <td class="p-2">{{ $item->sku }}</td>
                            <td class="p-2">{{ $item->name }}</td>
                            <td class="p-2">{{ $item->unit->symbol ?? '-' }}</td>
                            <td class="p-2">{{ $item->brand->name ?? '-' }}</td>
                            <td class="p-2">{{ $item->min_stock ?? '-' }}</td>
                            <td class="p-2 space-x-2">
                                <button wire:click="edit({{ $item->id }})"
                                    class="text-yellow-600 hover:text-white hover:bg-yellow-600 p-1 rounded transition"
                                    title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button"
                                    onclick="confirmAlert('Yakin ingin menghapus barang ini?', 'Ya, hapus!', () => @this.call('delete', {{ $item->id }}))"
                                    class="text-red-600 hover:text-white hover:bg-red-600 p-1 rounded transition"
                                    title="Hapus">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-gray-500 dark:text-gray-400 py-4">Tidak ada data
                                barang.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="mt-6">
            {{ $items->links() }}
        </div>

        {{-- Modal --}}
        <div x-data="{ open: @entangle('isModalOpen') }">
            <div x-show="open" class="fixed inset-0 bg-black/30 backdrop-blur-sm z-40"></div>
            <div x-show="open"
                class="fixed z-50 top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-md bg-white dark:bg-zinc-800 p-6 rounded shadow"
                x-transition>
                <h2 class="text-xl font-semibold mb-4">{{ $itemId ? 'Edit' : 'Tambah' }} Barang</h2>

                {{-- Nama --}}
                <div class="mb-4">
                    <label class="block mb-1">Nama</label>
                    <input type="text" wire:model.live="name"
                        class="w-full border rounded px-3 py-2 dark:bg-zinc-800 dark:text-white">
                    @error('name')
                        <small class="text-red-500">{{ $message }}</small>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="block mb-1">Satuan</label>
                    <input type="text" wire:model.live="unit_name"
                        wire:input="fetchSuggestions('unit', $event.target.value)" wire:blur="hideSuggestions('unit')"
                        class="w-full border rounded px-3 py-2 dark:bg-zinc-800 dark:text-white"
                        placeholder="Cari Satuan..." autocomplete="off">

                    @if ($suggestions['unit'])
                        <ul
                            class="absolute z-20 w-full bg-white border border-gray-300 rounded mt-1 max-h-60 overflow-auto">
                            @foreach ($suggestions['unit'] as $suggestion)
                                <li wire:click="selectSuggestion('unit', '{{ $suggestion }}')"
                                    class="px-4 py-2 bg-white dark:bg-zinc-800 text-gray-800 dark:text-white hover:bg-gray-100 dark:hover:bg-zinc-700 hover:text-black dark:hover:text-gray-200 cursor-pointer transition duration-150">
                                    {{ $suggestion }}
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                <div class="mb-4">
                    <label class="block mb-1">Merek</label>
                    <input type="text" wire:model.live="brand_name"
                        wire:input="fetchSuggestions('brand', $event.target.value)" wire:blur="hideSuggestions('brand')"
                        class="w-full border rounded px-3 py-2 dark:bg-zinc-800 dark:text-white"
                        placeholder="Cari Merek..." autocomplete="off">

                    @if ($suggestions['brand'])
                        <ul
                            class="absolute z-20 w-full bg-white border border-gray-300 rounded mt-1 max-h-60 overflow-auto">
                            @foreach ($suggestions['brand'] as $suggestion)
                                <li wire:click="selectSuggestion('brand', '{{ $suggestion }}')"
                                    class="px-4 py-2 bg-white dark:bg-zinc-800 text-gray-800 dark:text-white hover:bg-gray-100 dark:hover:bg-zinc-700 hover:text-black dark:hover:text-gray-200 cursor-pointer transition duration-150">
                                    {{ $suggestion }}
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                {{-- Min Stock --}}
                <div class="mb-4">
                    <label class="block mb-1">Stock Minimal</label>
                    <input type="number" wire:model.live="min_stock"
                        class="w-full border rounded px-3 py-2 dark:bg-zinc-800 dark:text-white">
                    @error('min_stock')
                        <small class="text-red-500">{{ $message }}</small>
                    @enderror
                </div>


                {{-- Gambar --}}
                <div class="mb-4">
                    <label class="block mb-1">Gambar</label>
                    <input type="file" wire:model="img"
                        class="w-full border rounded px-3 py-2 dark:bg-zinc-800 dark:text-white">
                    @error('img')
                        <small class="text-red-500">{{ $message }}</small>
                    @enderror

                    @if ($img)
                        <div class="mt-2">
                            <img src="{{ $img->temporaryUrl() }}" class="w-20 h-20 object-cover rounded shadow">
                        </div>
                    @endif
                </div>

                {{-- Tombol --}}
                <div class="flex justify-end space-x-2">
                    <button @click="open = false" class="bg-gray-300 px-4 py-2 rounded hover:bg-gray-400">
                        Batal
                    </button>
                    <button wire:click="save" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        Simpan
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-card>
