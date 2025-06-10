<x-card title="Data Laporan Stock">
    <div class="p-6">
        <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
            {{-- Kolom Kiri: Search + Filter --}}
            <div class="flex flex-wrap gap-2 w-full md:w-auto">
                <input type="text" wire:model.live="search" placeholder="Cari item..."
                    class="border px-4 py-2 rounded dark:bg-zinc-800 dark:text-white">

                <select wire:model.live="orderBy" class="border px-3 py-2 rounded dark:bg-zinc-800 dark:text-white">
                    <option value="created_at">Terbaru</option>
                    <option value="name">Nama</option>
                    <option value="sku">Kode</option>
                </select>

                <select wire:model.live="orderDirection"
                    class="border px-3 py-2 rounded dark:bg-zinc-800 dark:text-white">
                    <option value="asc">Naik</option>
                    <option value="desc">Turun</option>
                </select>
            </div>

            <div class="flex space-x-2">
                <button wire:click="exportExcel" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                    <i class="fas fa-file-excel"></i> <!-- Icon Excel -->
                </button>

                <button wire:click="exportPdf" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    <i class="fas fa-file-pdf"></i> <!-- Icon PDF -->
                </button>
            </div>
        </div>

        {{-- Filter Tanggal --}}
        <div class="flex gap-2 mb-4">
            <input type="date" wire:model.live="startDate"
                class="border px-4 py-2 rounded dark:bg-zinc-800 dark:text-white" />
            <input type="date" wire:model.live="endDate"
                class="border px-4 py-2 rounded dark:bg-zinc-800 dark:text-white" />
        </div>


        {{-- Tabel --}}
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white dark:bg-zinc-800 text-sm">
                <thead class="bg-gray-100 dark:bg-zinc-700 text-gray-700 dark:text-gray-300">
                    <tr>
                        <th class="p-2 text-left">Gambar</th>
                        <th class="p-2 text-left">Kode</th>
                        <th class="p-2 text-left">Nama</th>
                        <th class="p-2 text-left">Stok Saat Ini</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($itemsWithStock as $item)
                        <tr class="border-b dark:border-zinc-700">
                            <td class="p-2">
                                @if ($item['img'])
                                    <img src="{{ asset('storage/' . $item['img']) }}"
                                        class="w-12 h-12 object-cover rounded">
                                @else
                                    <img src="{{ asset('img/photo.png') }}" class="w-12 h-12 object-cover rounded">
                                @endif
                            </td>
                            <td class="p-2">{{ $item['sku'] }}</td>
                            <td class="p-2">{{ $item['item_name'] }}</td>
                            <td class="p-2">{{ $item['current_stock'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-gray-500 dark:text-gray-400 py-4">Tidak ada data
                                stok</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="mt-6">
            {{ $itemsWithStock->links() }}
        </div>
    </div>
</x-card>
