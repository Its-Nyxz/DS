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
                        <th class="p-2 text-left"></th>
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
                            <td class="p-2">
                                <button wire:click="showItemDetail('{{ $item['sku'] }}')"
                                    class="bg-indigo-600 text-white px-3 py-1 rounded text-sm hover:bg-indigo-700">
                                    Detail
                                </button>
                            </td>
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

        @if ($showItemDetailModal)
            <div class="fixed inset-0 z-50 flex items-center justify-center backdrop-blur-sm bg-black/30">
                <div
                    class="bg-white dark:bg-zinc-800 p-6 rounded shadow-lg w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                    <h2 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white">Detail Stok & Konversi</h2>

                    {{-- Info Barang + Supplier & Konversi --}}
                    @if (count($selectedItemDetail) > 0)
                        <div class="mb-4 border-b pb-4 border-gray-300 dark:border-zinc-600">
                            <p><strong>Nama Barang:</strong> {{ $selectedItemDetail[0]['Nama Barang'] }}</p>
                            <p><strong>Unit Dasar:</strong> {{ $selectedItemDetail[0]['Unit Dasar'] }}</p>

                            {{-- Pilih Supplier --}}
                            <div class="my-3">
                                <label class="block text-sm font-medium mb-1">Pilih Supplier</label>
                                <select wire:model.live="selectedSupplier" wire:change="updateAvailableConversions"
                                    class="w-full border px-3 py-2 rounded dark:bg-zinc-800 dark:text-white">
                                    @foreach ($availableSuppliers as $supplier)
                                        <option value="{{ $supplier['id'] }}">{{ $supplier['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Pilih Konversi --}}
                            <div class="mb-4">
                                <label class="block text-sm font-medium mb-1">Pilih Satuan Konversi</label>
                                <select wire:model.live="selectedConversionFactor"
                                    class="w-full border px-3 py-2 rounded dark:bg-zinc-800 dark:text-white">
                                    <option value="1">Tidak dikonversi</option>
                                    @foreach ($availableConversions as $conv)
                                        <option value="{{ $conv['factor'] }}">{{ $conv['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    @endif

                    {{-- Detail Stok --}}
                    {{-- Detail Stok --}}
                    <div class="mt-4 border-t pt-4">
                        <h3 class="font-semibold text-base mb-2">Detail Stok</h3>

                        @if (count($selectedItemStockDetails))
                            @php $stock = $selectedItemStockDetails[0]; @endphp

                            <table class="w-full text-sm bg-white dark:bg-zinc-800 border rounded overflow-hidden">
                                <tr>
                                    <td class="p-2">Jumlah Masuk</td>
                                    <td class="p-2 text-right">
                                        {{ number_format($stock['masuk'] * $selectedConversionFactor, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="p-2">Jumlah Keluar</td>
                                    <td class="p-2 text-right">
                                        {{ number_format($stock['keluar'] * $selectedConversionFactor, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="p-2">Retur Masuk</td>
                                    <td class="p-2 text-right">
                                        {{ number_format($stock['retur_in'] * $selectedConversionFactor, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="p-2">Retur Keluar</td>
                                    <td class="p-2 text-right">
                                        {{ number_format($stock['retur_out'] * $selectedConversionFactor, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="p-2">Penyesuaian</td>
                                    <td class="p-2 text-right">
                                        {{ number_format($stock['penyesuaian'] * $selectedConversionFactor, 2) }}</td>
                                </tr>
                                <tr class="font-bold bg-gray-100 dark:bg-zinc-700 text-green-600 dark:text-green-400">
                                    <td class="p-2">Total Stok</td>
                                    <td class="p-2 text-right">
                                        {{ number_format($stock['total'] * $selectedConversionFactor, 2) }}</td>
                                </tr>
                            </table>
                        @endif
                    </div>

                    <div class="mt-4 text-right">
                        <button wire:click="$set('showItemDetailModal', false)"
                            class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-card>
