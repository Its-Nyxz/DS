<x-card title="Data Supplier - Barang">
    <div class="p-6">

        {{-- Header --}}
        <div class="flex justify-between mb-4">
            <div class="flex flex-wrap gap-2 w-full md:w-auto">
                <input type="text" wire:model.live="search" placeholder="Cari pemasok..."
                    class="border px-4 py-2 rounded dark:bg-zinc-800 dark:text-white">

                <select wire:model.live="orderBy" class="border px-3 py-2 rounded dark:bg-zinc-800 dark:text-white">
                    <option value="created_at">Terbaru</option>
                    <option value="name">Nama</option>
                </select>

                <select wire:model.live="orderDirection"
                    class="border px-3 py-2 rounded dark:bg-zinc-800 dark:text-white">
                    <option value="asc">Naik</option>
                    <option value="desc">Turun</option>
                </select>
            </div>

            {{-- <button wire:click="openModal" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                Tambah Data
            </button> --}}
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white dark:bg-zinc-800 text-sm">
                {{-- Table Header --}}
                <thead class="bg-gray-100 dark:bg-zinc-700 text-gray-700 dark:text-gray-300">
                    <tr>
                        <th class="px-4 py-2 border w-3/4">Supplier</th>
                        <th class="px-4 py-2 border text-center w-1/2">Aksi</th>
                    </tr>
                </thead>

                {{-- Table Body --}}
                <tbody>
                    @forelse($suppliers as $supplier)
                        <tr class="border-b dark:border-zinc-700">
                            {{-- Supplier Name with Dropdown --}}
                            <td class="px-4 py-2 border">
                                <details class="group">
                                    <summary class="cursor-pointer font-semibold hover:underline">
                                        {{ $supplier->name }}
                                        <span class="text-blue-600 ml-2">({{ $supplier->items->count() }} Barang)</span>
                                    </summary>

                                    {{-- Barang Table --}}
                                    @if ($supplier->items->count())
                                        <div class="mt-3">
                                            <table class="w-full text-sm border mt-2 bg-white dark:bg-zinc-800">
                                                <thead class="bg-gray-50 dark:bg-zinc-600">
                                                    <tr>
                                                        <th class="p-2 border">Barang</th>
                                                        <th class="p-2 border">Harga Beli</th>
                                                        <th class="p-2 border">Utama</th>
                                                        <th class="p-2 border">Jumlah Minimum</th>
                                                        <th class="p-2 border">Waktu Tunggu</th>
                                                        <th class="p-2 border">Catatan</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($supplier->items as $item)
                                                        <tr>
                                                            <td class="p-2 border">
                                                                {{ $item->name }} - {{ $item->brand->name }}</td>
                                                            <td class="p-2 border">Rp
                                                                {{ number_format($item->pivot->harga_beli ?? 0, 0) }}
                                                            </td>
                                                            <td class="p-2 border">
                                                                {!! $item->pivot->is_default ? '<span class="text-green-600 font-semibold">Ya</span>' : 'Tidak' !!}
                                                            </td>
                                                            <td class="p-2 border">{{ $item->pivot->min_qty ?? '-' }}
                                                            </td>
                                                            <td class="p-2 border">
                                                                {{ $item->pivot->lead_time_days ? $item->pivot->lead_time_days . ' hari' : '-' }}
                                                            </td>
                                                            <td class="p-2 border">{{ $item->pivot->catatan ?? '-' }}
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif
                                </details>
                            </td>

                            {{-- Aksi Buttons --}}
                            <td class="px-4 py-2 border text-center">
                                <button wire:click="edit({{ $supplier->id }})"
                                    class="text-yellow-600 hover:text-white hover:bg-yellow-600 px-2 py-1 rounded transition"
                                    title="Edit Supplier">
                                    <i class="fas fa-edit"></i>
                                </button>

                                <button type="button"
                                    onclick="confirmAlert('Hapus semua Data dari {{ $supplier->name }}?', 'Ya, hapus!', () => @this.call('deleteAll', {{ $supplier->id }}))"
                                    class="text-red-600 hover:text-white hover:bg-red-600 px-2 py-1 rounded transition"
                                    title="Hapus Semua Data Supplier">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="text-center py-4 text-gray-500">
                                Tidak ada Data ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>



        {{-- Pagination --}}
        <div class="mt-6">
            {{ $suppliers->links() }}
        </div>

        {{-- Modal --}}
        <div x-data="{ open: @entangle('isModalOpen') }">
            <div x-show="open" class="fixed inset-0 bg-black/20 backdrop-blur-sm z-40"></div>
            <div x-show="open"
                class="fixed z-50 top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 
                w-full max-w-2xl sm:max-w-full md:max-w-3xl xl:max-w-4xl 2xl:max-w-4xl 
                sm:h-auto overflow-y-auto bg-white dark:bg-zinc-800 p-6 rounded shadow"
                x-transition>
                {{-- Scrollable Modal Body --}}
                <div class="overflow-y-auto max-h-[70vh] sm:max-h-[75vh] pb-24 space-y-3">
                    <h2 class="text-xl font-semibold mb-4">
                        {{ $supplierId ? 'Edit Data' : 'Tambah Data' }}
                    </h2>

                    {{-- Supplier --}}
                    <div class="mb-4 relative">
                        <label class="block mb-1 text-white">Pilih Supplier</label>
                        <input type="text" wire:model.live="supplier_name"
                            wire:focus="fetchSuggestions('supplier', supplier_name)"
                            wire:input="fetchSuggestions('supplier', $event.target.value)"
                            wire:blur="hideSuggestions('supplier')"
                            class="w-full border rounded px-3 py-2 dark:bg-zinc-800 dark:text-white"
                            placeholder="Cari Supplier..." autocomplete="off">

                        @if (!empty($suggestions['supplier']))
                            <ul
                                class="absolute z-10 w-full mt-1 rounded shadow text-sm border bg-white dark:bg-zinc-700 dark:text-white">
                                @foreach ($suggestions['supplier'] as $suggestion)
                                    <li wire:click="selectSuggestion('supplier', '{{ $suggestion }}')"
                                        class="px-3 py-2 hover:bg-gray-100 dark:hover:bg-zinc-600 cursor-pointer transition">
                                        {{ $suggestion }}
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        @error('supplierId')
                            <small class="text-red-500">{{ $message }}</small>
                        @enderror
                    </div>


                    {{-- Barang Input --}}
                    <div class="mb-4 space-y-3">
                        <label class="block mb-2 font-medium">Barang yang Disupply</label>

                        @foreach ($itemInputs as $index => $input)
                            <div
                                class="flex flex-col md:grid md:grid-cols-12 gap-2 items-start border p-3 rounded-md bg-gray-50 dark:bg-zinc-700">

                                {{-- Item --}}
                                <div class="w-full md:col-span-4 relative">
                                    <input type="text" wire:model.live="itemInputs.{{ $index }}.item_name"
                                        wire:input="fetchSuggestions('item', $event.target.value, {{ $index }})"
                                        wire:blur="hideSuggestions('item', {{ $index }})"
                                        class="w-full border rounded px-3 py-2" placeholder="Cari Barang..."
                                        autocomplete="off" />

                                    {{-- Suggestions --}}
                                    @if (!empty($suggestions['item'][$index]))
                                        <ul
                                            class="absolute z-10 bg-white dark:bg-zinc-700 w-full mt-1 rounded shadow text-sm">
                                            @foreach ($suggestions['item'][$index] as $suggestion)
                                                <li wire:click="selectSuggestion('item', {{ $index }}, {{ $suggestion['id'] }})"
                                                    class="px-3 py-2 hover:bg-gray-100 dark:hover:bg-zinc-600 cursor-pointer transition">
                                                    {{ $suggestion['label'] }}
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>


                                {{-- Harga beli --}}
                                <div class="w-full md:col-span-2">
                                    <input type="number" wire:model.live="itemInputs.{{ $index }}.harga_beli"
                                        class="w-full border rounded px-3 py-2" placeholder="Harga beli">
                                </div>

                                {{-- Min Qty --}}
                                <div class="w-full md:col-span-2">
                                    <input type="number" wire:model.live="itemInputs.{{ $index }}.min_qty"
                                        class="w-full border rounded px-3 py-2" placeholder="Jumlah Minimum">
                                </div>

                                {{-- Lead Time --}}
                                <div class="w-full md:col-span-2">
                                    <input type="number"
                                        wire:model.live="itemInputs.{{ $index }}.lead_time_days"
                                        class="w-full border rounded px-3 py-2" placeholder="Hari Tunggu">
                                </div>

                                {{-- Default --}}
                                <div class="w-full md:col-span-1 flex items-center space-x-1 mt-1">
                                    <input type="checkbox"
                                        wire:model.live="itemInputs.{{ $index }}.is_default">
                                    <span class="text-sm">Utama</span>
                                </div>

                                {{-- Hapus --}}
                                <div class="w-full md:col-span-1 flex justify-end mt-1">
                                    <button type="button" wire:click="removeItemInput({{ $index }})"
                                        class="text-red-500 hover:underline text-sm">Hapus</button>
                                </div>

                                {{-- Catatan --}}
                                <div class="w-full md:col-span-12 mt-2">
                                    <input type="text" wire:model.live="itemInputs.{{ $index }}.catatan"
                                        class="w-full border rounded px-3 py-2" placeholder="Catatan opsional">
                                </div>

                                {{-- Konversi Satuan --}}
                                <div class="md:col-span-12 mt-3">
                                    <label class="block text-sm font-semibold mb-2">Konversi Satuan</label>

                                    @php
                                        $itemUnitId = \App\Models\Item::find($input['item_id'])?->unit_id;
                                        $itemUnit = \App\Models\Unit::find($itemUnitId);
                                    @endphp

                                    {{-- Info satuan asal --}}
                                    <div class="text-sm mb-2">
                                        Dari Satuan:
                                        <span class="font-semibold">
                                            {{ $itemUnit->name ?? '-' }} ({{ $itemUnit->symbol ?? '' }})
                                        </span>
                                    </div>

                                    {{-- Daftar konversi --}}
                                    @foreach ($input['conversions'] ?? [] as $convIndex => $conv)
                                        <div class="flex gap-2 items-center mb-2">
                                            <select
                                                wire:model="itemInputs.{{ $index }}.conversions.{{ $convIndex }}.to_unit_id"
                                                class="border px-2 py-1 rounded w-1/3 dark:bg-zinc-700 dark:text-white">
                                                <option value="">Ke Satuan</option>
                                                @foreach ($units as $unit)
                                                    <option value="{{ $unit->id }}">
                                                        {{ $unit->name }} ({{ $unit->symbol }})
                                                    </option>
                                                @endforeach
                                            </select>

                                            <input type="number" step="0.01"
                                                wire:model="itemInputs.{{ $index }}.conversions.{{ $convIndex }}.factor"
                                                class="border px-2 py-1 rounded w-1/3" placeholder="Faktor">

                                            <button type="button"
                                                wire:click="removeConversion({{ $index }}, {{ $convIndex }})"
                                                class="text-red-600 text-sm hover:underline">
                                                Hapus
                                            </button>
                                        </div>
                                    @endforeach

                                    {{-- Tombol tambah konversi --}}
                                    <button type="button" wire:click="addConversion({{ $index }})"
                                        class="text-blue-600 text-sm hover:underline mt-1">
                                        + Tambah Konversi
                                    </button>
                                </div>

                            </div>
                        @endforeach


                        <button type="button" wire:click="addItemInput"
                            class="text-sm text-blue-600 hover:underline mt-2">+ Tambah Barang</button>
                    </div>


                    <div class="flex flex-col sm:flex-row justify-end space-y-2 sm:space-y-0 sm:space-x-2 mt-4">
                        <button @click="open = false"
                            class="w-full sm:w-auto bg-gray-300 px-4 py-2 rounded hover:bg-gray-400">Batal</button>
                        <button type="button" wire:click="save"
                            class="w-full sm:w-auto bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                            Simpan
                        </button>
                    </div>
                </div> {{-- end scrollable --}}
            </div>
        </div>
    </div>
</x-card>
