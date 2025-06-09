@php
    $labels = [
        'in' => 'Masuk',
        'out' => 'Keluar',
        'retur' => 'Retur',
        'opname' => 'Stock Opname',
    ];
@endphp
<div>
    <x-card title="Transaksi Barang {{ $labels[$type] ?? ucfirst($type) }}">
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 gap-3">
            <div class="flex flex-wrap gap-2 w-full md:w-auto">
                <input type="text" wire:model.live="search" placeholder="Cari..."
                    class="border px-4 py-2 rounded w-1/3 dark:bg-zinc-800 dark:text-white">
                <select wire:model.live="orderBy"
                    class="border rounded p-2 dark:bg-zinc-800 dark:text-white dark:border-zinc-600">
                    <option value="transaction_date">Tanggal</option>
                    <option value="transaction_code">Kode Transaksi</option>
                    <option value="total">Total</option>
                </select>

                <select wire:model.live="orderDirection"
                    class="border rounded p-2 dark:bg-zinc-800 dark:text-white dark:border-zinc-600">
                    <option value="asc">Naik</option>
                    <option value="desc">Turun</option>
                </select>
            </div>

            <button wire:click="create" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                + Tambah Transaksi
            </button>
        </div>


        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white dark:bg-zinc-800 text-sm">
                <thead class="bg-gray-100 dark:bg-zinc-700 text-gray-700 dark:text-gray-300">
                    <tr>
                        <th class="px-4 py-3 border">Tanggal</th>
                        <th class="px-4 py-3 border">Kode</th>
                        @if ($type === 'in')
                            <th class="px-4 py-3 border">Supplier</th>
                        @elseif ($type === 'out')
                            <th class="px-4 py-3 border">Customer</th>
                        @elseif ($type === 'retur')
                            <th class="px-4 py-3 border">Dari</th>
                        @endif
                        <th class="px-4 py-3 border">Item</th>
                        @if ($type === 'retur')
                            <th class="px-4 py-3 border">Tipe</th>
                        @endif
                        <th class="px-4 py-3 border">Total</th>
                        <th class="px-4 py-3 border">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transactions as $tx)
                        <tr class="border-b dark:border-zinc-700">
                            <td class="px-4 py-2">{{ $tx->transaction_date ?? $tx->created_at->format('d/m/Y') }}</td>
                            <td class="px-4 py-2">{{ $tx->transaction_code }}</td>
                            <td class="px-4 py-2">
                                @if ($tx->type === 'in')
                                    {{ $tx->supplier->name ?? '-' }}
                                @elseif ($tx->type === 'out')
                                    {{ $tx->customer->name ?? '-' }}
                                @elseif ($tx->type === 'retur')
                                    {{ $tx->customer->name ?? '-' }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-4 py-2 text-right">
                                {{ $tx->items->count() }}
                            </td>
                            @if ($type === 'retur')
                                <td class="px-4 py-2">
                                    @if ($tx->type === 'retur_in')
                                        <span class="text-green-600 font-medium">Retur dari Customer</span>
                                    @elseif ($tx->type === 'retur_out')
                                        <span class="text-yellow-600 font-medium">Retur ke Supplier</span>
                                    @else
                                        <span class="text-gray-500">-</span>
                                    @endif
                                </td>
                            @endif
                            <td class="px-4 py-2 text-right">
                                Rp {{ number_format($tx->items->sum('subtotal'), 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-2 text-center align-middle flex justify-center items-center gap-2">
                                {{-- Status label --}}
                                @if ($type === 'in')
                                    @if ($tx->is_approved)
                                        <span
                                            class="text-green-600 text-xs font-semibold bg-green-100 px-2 py-1 rounded">Approved</span>
                                    @else
                                        <span
                                            class="text-yellow-600 text-xs font-semibold bg-yellow-100 px-2 py-1 rounded">Pending</span>
                                    @endif
                                @endif

                                {{-- Tombol Edit --}}
                                <button wire:click="edit({{ $tx->id }})"
                                    class="text-yellow-600 hover:text-white hover:bg-yellow-600 p-1 rounded"
                                    title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>

                                {{-- Tombol Hapus --}}
                                <button
                                    onclick="confirmAlert('Hapus transaksi {{ $tx->transaction_code }}?', 'Ya, hapus!', () => @this.call('delete', {{ $tx->id }}))"
                                    class="text-red-600 hover:text-white hover:bg-red-600 p-1 rounded" title="Hapus">
                                    <i class="fas fa-trash-alt"></i>
                                </button>

                                {{-- Tombol Detail --}}
                                <button wire:click="showDetail({{ $tx->id }})"
                                    class="text-blue-600 hover:text-white hover:bg-blue-600 p-1 rounded"
                                    title="Lihat Detail">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-gray-500 py-4">Tidak ada transaksi ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="mt-6">
            {{ $transactions->links() }}
        </div>

        {{-- Modal Form --}}
        <div x-data="{ open: @entangle('isModalOpen') }">
            <div x-show="open" class="fixed inset-0 bg-black/40 z-40"></div>

            <div x-show="open"
                class="fixed top-1/2 left-1/2 w-full max-w-4xl transform -translate-x-1/2 -translate-y-1/2 z-50
           bg-white dark:bg-zinc-800 rounded p-6 shadow-lg overflow-y-auto max-h-[90vh]">

                <h2 class="text-xl font-bold mb-4">
                    {{ $editingId ? 'Edit' : 'Tambah' }} Transaksi {{ $labels[$type] ?? ucfirst($type) }}
                </h2>

                <div class="mb-3">
                    <label class="block text-sm mb-1 dark:text-white">Kode Transaksi</label>
                    <input type="text"
                        class="w-full border rounded p-2 bg-gray-100 dark:bg-zinc-700 dark:text-white dark:border-zinc-700"
                        wire:model="transaction_code" disabled>
                </div>
                @if ($type === 'retur')
                    <div class="mb-4">
                        <label>Tipe Retur</label>
                        <select wire:model.live="subtype"
                            class="w-full border rounded p-2 bg-white dark:bg-zinc-800 dark:text-white dark:border-zinc-600">
                            <option value="">Semua</option>
                            <option value="retur_in">Retur dari Customer</option>
                            <option value="retur_out">Retur dari Supplier</option>
                        </select>
                    </div>
                @endif

                @if ($type === 'retur')
                    @if ($subtype === 'retur_out')
                        {{-- Supplier untuk Retur ke Supplier --}}
                        <div class="mb-3">
                            <label class="block mb-1 text-sm dark:text-white">Supplier</label>
                            <select wire:model.live="supplier_id"
                                class="w-full border rounded p-2 bg-white dark:bg-zinc-800 dark:text-white dark:border-zinc-600">
                                <option value="">-- Pilih Supplier --</option>
                                @foreach ($suppliers as $s)
                                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                                @endforeach
                            </select>
                            @error('supplier_id')
                                <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>
                    @elseif($subtype === 'retur_in')
                        {{-- Input Nama Customer --}}
                        <div class="mb-3">
                            <label class="block mb-1 text-sm dark:text-white">Customer</label>
                            <input type="text" wire:model.live="customer_name"
                                class="w-full border rounded p-2 dark:bg-zinc-800 dark:text-white dark:border-zinc-600"
                                placeholder="Nama customer...">
                            @error('customer_name')
                                <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>
                    @endif
                @elseif ($type === 'in')
                    {{-- Supplier untuk Transaksi Masuk --}}
                    <div class="mb-3">
                        <label class="block mb-1 text-sm dark:text-white">Supplier</label>
                        <select wire:model.live="supplier_id"
                            class="w-full border rounded p-2 bg-white dark:bg-zinc-800 dark:text-white dark:border-zinc-600">
                            <option value="">-- Pilih Supplier --</option>
                            @foreach ($suppliers as $s)
                                <option value="{{ $s->id }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                        @error('supplier_id')
                            <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>
                @elseif($type === 'out')
                    {{-- Input Nama Customer --}}
                    <div class="mb-3">
                        <label class="block mb-1 text-sm dark:text-white">Customer</label>
                        <input type="text" wire:model.live="customer_name"
                            class="w-full border rounded p-2 dark:bg-zinc-800 dark:text-white dark:border-zinc-600"
                            placeholder="Nama customer...">
                        @error('customer_name')
                            <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>
                @endif


                {{-- Tanggal --}}
                <div class="mb-3">
                    <label>Tanggal Transaksi</label>
                    <input type="date" wire:model.live="transaction_date" class="w-full border rounded p-2">
                </div>

                {{-- Catatan --}}
                <div class="mb-3">
                    <label>Catatan</label>
                    <textarea wire:model.live="description" class="w-full border rounded p-2"></textarea>
                </div>

                {{-- Items --}}
                <div class="mb-3">
                    <h3 class="font-semibold mb-2">Detail Barang</h3>
                    @foreach ($items as $i => $row)
                        @php
                            $itemSupplier = $itemSuppliers->firstWhere('id', $row['item_supplier_id'] ?? null);
                            $conversions = $itemSupplier?->unitConversions ?? [];
                        @endphp
                        <div
                            class="bg-white dark:bg-zinc-800 p-4 rounded shadow mb-4 space-y-3 border border-gray-200 dark:border-zinc-700">

                            {{-- Baris 1: Barang & Konversi --}}
                            <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
                                {{-- Barang --}}
                                <div class="md:col-span-3">
                                    <select wire:model.live="items.{{ $i }}.item_supplier_id"
                                        wire:change="setItemSupplier({{ $i }}, $event.target.value)"
                                        class="w-full border rounded p-2  bg-white text-gray-800 border-gray-300 dark:bg-zinc-800 dark:text-white dark:border-zinc-700">
                                        <option value="">-- Pilih Barang --</option>
                                        @foreach ($itemSuppliers as $is)
                                            <option value="{{ $is->id }}">
                                                {{ $is->item->name }} ({{ $is->item->brand->name }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error("items.{$i}.item_supplier_id")
                                        <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                                    @enderror
                                </div>

                                @if (in_array($type, ['out', 'opname']))
                                    {{-- Konversi --}}
                                    <div class="md:col-span-2">
                                        <select wire:model.live="items.{{ $i }}.selected_unit_id"
                                            class="w-full border rounded p-2 bg-white text-gray-800 border-gray-300 dark:bg-zinc-800 dark:text-white dark:border-zinc-700">
                                            <option value="">Konversi Satuan</option>
                                            @foreach ($conversions as $conv)
                                                <option value="{{ $conv->to_unit_id }}">
                                                    {{ $conv->toUnit->symbol }} - Faktor: {{ $conv->factor }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif
                            </div>

                            {{-- Baris 2: Qty, Harga, Subtotal, Hapus --}}
                            <div class="flex flex-col md:flex-row justify-between gap-3 items-start">
                                {{-- Qty --}}
                                <div class="relative">
                                    <input type="number" wire:model.live="items.{{ $i }}.quantity"
                                        class="w-full border rounded p-2 pr-16  bg-white text-gray-800 border-gray-300 dark:bg-zinc-800 dark:text-white dark:border-zinc-700"
                                        min="1" placeholder="Qty">

                                    @php
                                        $selectedUnitId = $row['selected_unit_id'] ?? null;
                                        $conversion = $itemSupplier?->unitConversions->firstWhere(
                                            'to_unit_id',
                                            $selectedUnitId,
                                        );
                                        $qtySymbol = $selectedUnitId
                                            ? $conversion?->toUnit?->symbol ?? '?'
                                            : $itemSupplier?->item?->unit?->symbol ?? '?';
                                    @endphp

                                    <span
                                        class="absolute top-2 right-2 text-sm text-zinc-400">{{ $qtySymbol }}</span>
                                    <div class="mt-1 text-red-500 text-xs">
                                        @error("items.$i.quantity")
                                            {{ $message }}
                                        @enderror
                                    </div>
                                </div>


                                {{-- Harga --}}
                                <div class="relative">
                                    <input type="number" wire:model.live="items.{{ $i }}.unit_price"
                                        class="w-full border rounded p-2 pr-16  bg-white text-gray-800 border-gray-300 dark:bg-zinc-800 dark:text-white dark:border-zinc-700"
                                        step="0.01">
                                    @php
                                        $selectedUnitId = $row['selected_unit_id'] ?? null;
                                        $conversion = $itemSupplier?->unitConversions->firstWhere(
                                            'to_unit_id',
                                            $selectedUnitId,
                                        );
                                        $unitSymbol = $selectedUnitId
                                            ? $conversion?->toUnit?->symbol ?? '?'
                                            : $itemSupplier?->item?->unit?->symbol ?? '?';
                                    @endphp
                                    <span class="absolute top-2 right-2 text-sm text-zinc-400">per
                                        {{ $unitSymbol }}</span>
                                </div>

                                {{-- Subtotal & Tombol Hapus Rata Kanan --}}
                                <div class="flex justify-end items-center mt-2 text-white gap-4">
                                    <div class="flex items-center gap-2 text-green-400">
                                        <i class="fas fa-money-bill-wave"></i>
                                        <span>
                                            Rp
                                            {{ number_format((float) ($row['quantity'] ?? 0) * (float) ($row['unit_price'] ?? 0), 0, ',', '.') }}
                                        </span>
                                    </div>

                                    <button wire:click="removeItem({{ $i }})"
                                        class="w-8 h-8 flex items-center justify-center text-red-600 hover:text-white hover:bg-red-600 rounded-full transition"
                                        title="Hapus">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                    <button wire:click="addItem" class="text-blue-600 mt-2 hover:underline">+ Tambah Barang</button>
                    {{-- Total --}}
                    <div class="flex justify-end font-semibold text-lg mt-4">
                        Total: Rp {{ number_format($total, 0, ',', '.') }}
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex justify-end space-x-2 mt-6">
                    <button @click="open = false" class="px-4 py-2 bg-gray-300 rounded">Batal</button>
                    <button wire:click="save" class="px-4 py-2 bg-blue-600 text-white rounded">Simpan</button>
                </div>
            </div>
        </div>

        {{-- Modal Detail --}}
        <div x-data="{ open: @entangle('isDetailOpen') }">
            <div x-show="open" class="fixed inset-0 bg-black/40 z-40"></div>

            <div x-show="open"
                class="fixed top-1/2 left-1/2 w-full max-w-2xl transform -translate-x-1/2 -translate-y-1/2 z-50 bg-white dark:bg-zinc-800 rounded p-6 shadow-lg">

                {{-- Header --}}
                <h2 class="text-xl font-bold mb-4">Detail Transaksi</h2>

                {{-- Informasi Umum --}}
                <div class="mb-2"><strong>Kode:</strong> {{ $detail['code'] ?? '' }}</div>
                <div class="mb-2"><strong>Tanggal:</strong> {{ $detail['date'] ?? '' }}</div>
                <div class="mb-2">
                    <strong>
                        @if (($detail['type'] ?? '') === 'retur_in' || ($detail['type'] ?? '') === 'retur_out')
                            Customer:
                        @elseif (($detail['type'] ?? '') === 'out')
                            Customer:
                        @else
                            Supplier:
                        @endif
                    </strong>

                    {{-- Tampilkan customer atau supplier sesuai tipe transaksi --}}
                    @if (($detail['type'] ?? '') === 'retur_in' || ($detail['type'] ?? '') === 'out')
                        {{ $detail['customer_name'] ?? '-' }}
                    @elseif (($detail['type'] ?? '') === 'retur_out')
                        {{ $detail['supplier'] ?? '-' }}
                    @else
                        {{ $detail['supplier'] ?? '-' }}
                    @endif
                </div>

                @if (($detail['type'] ?? '') === 'retur_in')
                    <div class="mb-2 text-green-600 font-medium">Tipe: Retur dari Customer</div>
                @elseif (($detail['type'] ?? '') === 'retur_out')
                    <div class="mb-2 text-yellow-600 font-medium">Tipe: Retur ke Supplier</div>
                @endif

                {{-- Item List --}}
                <div class="mb-4">
                    @foreach ($detail['items'] ?? [] as $item)
                        <div class="grid grid-cols-4 gap-4 mb-2 text-sm">
                            {{-- Nama Barang --}}
                            <div class="flex items-center">
                                <span class="font-medium">{{ $item['name'] }} ({{ $item['brand'] }})</span>
                            </div>

                            {{-- Quantity --}}
                            <div class="flex justify-end items-center">
                                <span>{{ $item['converted_qty'] }} {{ $item['unit_symbol'] }}</span>
                            </div>

                            {{-- Harga --}}
                            <div class="flex justify-end items-center">
                                <span>Rp {{ number_format($item['price'], 0, ',', '.') }}</span>
                            </div>

                            {{-- Subtotal --}}
                            <div class="flex justify-end items-center">
                                <span>Rp {{ number_format($item['subtotal'], 0, ',', '.') }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Total --}}
                <div class="text-right font-bold">
                    Total: Rp {{ number_format($detail['total'] ?? 0, 0, ',', '.') }}
                </div>

                {{-- Catatan --}}
                <div class="mt-2 text-sm">
                    <strong>Catatan:</strong> {{ $detail['note'] ?? '-' }}
                </div>

                {{-- Tombol Approve / Reject --}}
                @if (
                    !empty($detail['id']) &&
                        !$detail['is_approved'] &&
                        ($detail['type'] ?? '') === 'in' &&
                        auth()->user()->hasRole('pemilik'))
                    <div class="flex gap-2 mt-4">
                        <button wire:click="approve({{ $detail['id'] }})"
                            class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                            Approve
                        </button>
                        <button wire:click="reject({{ $detail['id'] }})"
                            class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                            Reject
                        </button>
                    </div>
                @endif

                {{-- Tombol Tutup --}}
                <div class="flex justify-end mt-6">
                    <button @click="open = false" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">
                        Tutup
                    </button>
                </div>
            </div>

        </div>

    </x-card>
</div>
