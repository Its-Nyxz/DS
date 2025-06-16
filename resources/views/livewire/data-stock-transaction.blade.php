@php
    $labels = [
        'in' => 'Transaksi Masuk',
        'out' => 'Transaksi Keluar',
        'retur' => 'Transaksi Retur',
        'opname' => 'Stock Opname',
    ];

    $field = [
        'in' => 'Masuk',
        'out' => 'Keluar',
        'retur' => 'Retur',
        'opname' => 'Stock Opname',
    ];

    $differenceReasons = [
        'damaged' => 'Rusak',
        'stolen' => 'Dicuri',
        'clerical_error' => 'Kesalahan Administrasi',
        'other' => 'Lainnya',
    ];
    $opnameTypes = ['regular' => 'Reguler', 'audit' => 'Audit', 'ad_hoc' => 'Ad-hoc'];
@endphp
<div>
    <x-card title="Barang {{ $labels[$type] ?? ucfirst($type) }}">
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 gap-3">
            <div class="flex flex-wrap gap-2 w-full md:w-auto">
                <input type="text" wire:model.live="search" placeholder="Cari..."
                    class="border px-4 py-2 rounded w-1/3 dark:bg-zinc-800 dark:text-white">
                <select wire:model.live="orderBy"
                    class="border rounded p-2 dark:bg-zinc-800 dark:text-white dark:border-zinc-600">
                    <option value="transaction_date">Tanggal</option>
                    <option value="transaction_code">Kode {{ $field[$type] ?? ucfirst($type) }}</option>
                    @if ($type !== 'opname')
                        <option value="total">Total</option>
                    @else
                        <option value="opname_type">Jenis Opname</option>
                        <option value="difference_reason">Alasan</option>
                    @endif
                </select>

                <select wire:model.live="orderDirection"
                    class="border rounded p-2 dark:bg-zinc-800 dark:text-white dark:border-zinc-600">
                    <option value="asc">Naik</option>
                    <option value="desc">Turun</option>
                </select>
                @if ($type === 'in')
                    <select wire:model.live="is_approved"
                        class="border rounded p-2 dark:bg-zinc-800 dark:text-white dark:border-zinc-600">
                        <option value="">Semua Status</option>
                        <option value="0">Pending</option>
                        <option value="1">Approve</option>
                    </select>
                @endif
                <div class="flex flex-wrap gap-2">
                    <input type="date" wire:model.live="startDate"
                        class="border px-2 py-1 rounded dark:bg-zinc-800 dark:text-white" placeholder="Dari tanggal">

                    <input type="date" wire:model.live="endDate"
                        class="border px-2 py-1 rounded dark:bg-zinc-800 dark:text-white" placeholder="Sampai tanggal">
                </div>
            </div>
            <div class="flex gap-2 mt-2 sm:mt-0">
                <button wire:click="exportPdfByType"
                    class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 flex items-center gap-1">
                    <i class="fas fa-file-pdf"></i>
                </button>

                <button wire:click="create" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    + Tambah {{ $field[$type] ?? ucfirst($type) }}
                </button>
            </div>
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
                        <th class="px-4 py-3 border">Barang</th>
                        @if ($type === 'opname')
                            <th class="px-4 py-3 border">Jenis Opname</th>
                            <th class="px-4 py-3 border">Alasan</th>
                        @endif
                        @if ($type === 'retur')
                            <th class="px-4 py-3 border">Tipe</th>
                        @endif
                        @if ($type !== 'opname')
                            <th class="px-4 py-3 border">Total</th>
                        @endif

                        <th class="px-4 py-3 border">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transactions as $tx)
                        <tr class="border-b dark:border-zinc-700">
                            <td class="px-4 py-2">
                                {{ optional($tx->transaction_date)->format('d/m/Y H:i') ?? $tx->created_at->format('d/m/Y H:i') }}
                            </td>

                            <td class="px-4 py-2">{{ $tx->transaction_code }}</td>

                            @if ($type !== 'opname')
                                <td class="px-4 py-2">
                                    @if ($tx->type === 'in')
                                        {{ $tx->supplier->name ?? '-' }}
                                    @elseif ($tx->type === 'out')
                                        {{ $tx->customer->name ?? '-' }}
                                    @elseif ($tx->type === 'retur_out')
                                        {{ $tx->supplier->name ?? '-' }}
                                    @elseif ($tx->type === 'retur_in')
                                        {{ $tx->customer->name ?? '-' }}
                                    @endif
                                </td>
                            @endif
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
                            @if ($type === 'opname')
                                <td class="px-4 py-2">
                                    {{ $opnameTypes[$tx->opname_type] ?? '-' }}
                                </td>

                                <td class="px-4 py-2">
                                    {{ $differenceReasons[$tx->difference_reason] ?? '-' }}
                                </td>
                            @endif
                            @if ($type !== 'opname')
                                <td class="px-4 py-2 text-right">
                                    Rp {{ number_format($tx->items->sum('subtotal'), 0, ',', '.') }}
                                </td>
                            @endif
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
                                    @if ($tx->is_fully_paid)
                                        <button wire:click="openPaymentDetailModal({{ $tx->id }})"
                                            class="text-blue-600 text-xs font-semibold bg-blue-100 hover:bg-blue-200 px-2 py-1 rounded transition"
                                            title="Lihat detail pembayaran">
                                            Lunas
                                        </button>
                                    @else
                                        <button wire:click="openPaymentModal({{ $tx->id }})"
                                            class="text-red-600 text-xs font-semibold bg-red-100 hover:bg-red-200 px-2 py-1 rounded transition"
                                            title="Klik untuk bayar termin">
                                            Hutang
                                        </button>
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
            <div x-show="open" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-40"></div>

            <div x-show="open"
                class="fixed top-1/2 left-1/2 w-full max-w-4xl transform -translate-x-1/2 -translate-y-1/2 z-50
    bg-white dark:bg-zinc-800 rounded p-6 shadow-lg overflow-y-auto max-h-[90vh]">

                <h2 class="text-xl font-bold mb-4">
                    {{ $editingId ? 'Edit' : 'Tambah' }} {{ $labels[$type] ?? ucfirst($type) }}
                </h2>

                <div class="mb-3">
                    <label class="block text-sm mb-1 dark:text-white">Kode
                        {{ $labels[$type] ?? ucfirst($type) }}</label>
                    <input type="text"
                        class="w-full border rounded p-2 bg-gray-100 dark:bg-zinc-700 dark:text-white dark:border-zinc-700"
                        wire:model="transaction_code" disabled>
                </div>

                @if ($type === 'opname')
                    {{-- Pilih Alasan Perbedaan --}}
                    <div class="mb-3">
                        <label class="block text-sm mb-1 dark:text-white">Alasan Perbedaan</label>
                        <select wire:model.live="difference_reason"
                            class="w-full border rounded p-2 bg-white dark:bg-zinc-800 dark:text-white dark:border-zinc-600">
                            <option value="">Pilih Alasan</option>
                            @foreach ($differenceReasons as $key => $reason)
                                <option value="{{ $key }}">{{ $reason }}</option>
                            @endforeach
                        </select>
                        @error('difference_reason')
                            <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Pilih Jenis Opname --}}
                    <div class="mb-3">
                        <label class="block text-sm mb-1 dark:text-white">Jenis Opname</label>
                        <select wire:model.live="opname_type"
                            class="w-full border rounded p-2 bg-white dark:bg-zinc-800 dark:text-white dark:border-zinc-600">
                            <option value="">Pilih Jenis Opname</option>
                            @foreach ($opnameTypes as $key => $optype)
                                <option value="{{ $key }}">{{ $optype }}</option>
                            @endforeach
                        </select>
                        @error('opname_type')
                            <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>
                @endif

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
                                class="w-full border rounded p-2 bg-white dark:bg-zinc-800 dark:text-white dark:border-zinc-600 overflow-y-auto max-h-60">
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
                            class="w-full border rounded p-2 bg-white dark:bg-zinc-800 dark:text-white dark:border-zinc-600 overflow-y-auto max-h-60">
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
                    <input type="datetime-local" wire:model.live="transaction_date"
                        class="w-full border rounded p-2">
                </div>

                {{-- Catatan --}}
                <div class="mb-3">
                    <label>Catatan</label>
                    <textarea wire:model.live="description" class="w-full border rounded p-2"></textarea>
                </div>

                @if ($type === 'in')
                    <div class="mb-4">
                        <label class="block font-semibold mb-1">Jenis Pembayaran</label>
                        <label class="inline-flex items-center mr-4">
                            <input type="radio" wire:model.live="payment_type" value="cash" class="form-radio">
                            <span class="ml-2">Tunai</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="radio" wire:model.live="payment_type" value="term" class="form-radio">
                            <span class="ml-2">Termin</span>
                        </label>
                    </div>

                    @if ($payment_type === 'term')
                        <div class="mb-3">
                            <h4 class="font-semibold text-sm mb-2">Termin Pembayaran</h4>

                            @foreach ($payment_schedules as $i => $schedule)
                                <div class="grid grid-cols-12 items-start gap-2 mb-2">
                                    {{-- Nominal --}}
                                    <div class="col-span-5">
                                        <input type="number"
                                            wire:model.live="payment_schedules.{{ $i }}.amount"
                                            placeholder="Jumlah (Rp)"
                                            class="w-full border rounded p-2 @error('payment_schedules.' . $i . '.amount') border-red-500 @enderror" />
                                        <small class="text-gray-500">* Jumlah adalah nominal dalam rupiah</small>
                                    </div>

                                    {{-- Tanggal --}}
                                    <div class="col-span-5">
                                        <input type="date"
                                            wire:model.live="payment_schedules.{{ $i }}.due_date"
                                            class="w-full border rounded p-2 @error('payment_schedules.' . $i . '.due_date') border-red-500 @enderror" />
                                    </div>

                                    {{-- Tombol hapus --}}
                                    <div class="col-span-2 flex items-center">
                                        <button type="button"
                                            wire:click="removePaymentSchedule({{ $i }})"
                                            class="text-red-500 hover:underline text-sm">Hapus</button>
                                    </div>
                                </div>
                            @endforeach

                            {{-- Total termin --}}
                            <div class="mt-2 text-sm text-right text-gray-600">
                                Total Termin: Rp
                                {{ number_format(collect($payment_schedules)->sum('amount'), 0, ',', '.') }}
                            </div>

                            {{-- Tombol tambah termin --}}
                            <button type="button" wire:click="addPaymentSchedule"
                                class="text-blue-500 hover:underline text-sm mt-1">+ Tambah Termin</button>
                        </div>
                    @endif
                @endif

                {{-- Items --}}
                <div class="mb-3">
                    <h3 class="font-semibold mb-2">Detail Barang</h3>
                    @foreach ($items as $i => $row)
                        @php
                            $itemSupplier = $itemSuppliers->firstWhere('id', $row['item_supplier_id'] ?? null);
                            $conversions = $row['unit_conversions'] ?? [];
                        @endphp
                        <div
                            class="bg-white dark:bg-zinc-800 p-4 rounded shadow mb-4 space-y-3 border border-gray-200 dark:border-zinc-700">

                            {{-- Baris 1: Barang & Konversi --}}
                            <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
                                {{-- Barang --}}
                                <div class="md:col-span-3">
                                    <select wire:model.live="items.{{ $i }}.item_supplier_id"
                                        wire:change="setItemSupplier({{ $i }}, $event.target.value)"
                                        class="w-full border rounded p-2  bg-white text-gray-800 border-gray-300 dark:bg-zinc-800 dark:text-white dark:border-zinc-700 overflow-y-auto max-h-60">
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

                                @if (in_array($type, ['out', 'opname']) || ($type === 'retur' && $subtype === 'retur_in'))
                                    {{-- Konversi --}}
                                    <div class="md:col-span-2">
                                        <select wire:model.live="items.{{ $i }}.selected_unit_id"
                                            class="w-full border rounded p-2 bg-white text-gray-800 border-gray-300 dark:bg-zinc-800 dark:text-white dark:border-zinc-700 overflow-y-auto max-h-60">
                                            <option value="">Konversi Satuan</option>
                                            @foreach ($conversions as $conv)
                                                <option value="{{ (int) $conv['to_unit_id'] }}"
                                                    {{ $conv['to_unit_id'] == $row['selected_unit_id'] ? 'selected' : '' }}>
                                                    {{ $conv['to_unit']['symbol'] ?? '-' }} - Faktor:
                                                    {{ $conv['factor'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif
                            </div>

                            {{-- Baris 2: Qty, Harga, Subtotal, Hapus --}}
                            <div class="flex flex-col md:flex-row justify-between gap-3 items-start">
                                {{-- System Stock --}}
                                @if ($type === 'opname')
                                    <div class="relative">
                                        <input type="number"
                                            wire:model.live="items.{{ $i }}.system_stock"
                                            class="w-full border rounded p-2 pr-16 bg-gray-100 text-gray-800 border-gray-300 dark:bg-zinc-700 dark:text-white dark:border-zinc-700"
                                            placeholder="Sistem" readonly>

                                        <span class="absolute top-2 right-2 text-sm text-zinc-400">
                                            {{ $row['unit_symbol'] ?? '-' }}
                                        </span>
                                    </div>
                                @endif

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

                                @if ($type === 'opname')
                                    <div class="relative">
                                        @php
                                            $getStock = $row['get_stock'] ?? 0;
                                            $status = $row['status'] ?? '';
                                            $displayStock = $status === 'tambah' ? abs($getStock) : $getStock;
                                        @endphp
                                        <input type="number" value="{{ number_format($displayStock, 2) }}"
                                            class="w-full border rounded p-2 pr-16 bg-gray-100 text-gray-800 border-gray-300 dark:bg-zinc-700 dark:text-white dark:border-zinc-700"
                                            placeholder="Penyesuaian" readonly>

                                        <span class="absolute top-2 right-2 text-sm text-zinc-400">
                                            {{ $row['unit_symbol'] ?? '-' }}
                                        </span>
                                    </div>
                                    <div class="relative mt-2.5">
                                        @php
                                            $status = $row['status'] ?? null;
                                        @endphp

                                        <span
                                            class="
                                                inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold
                                                @if ($status === 'tambah') bg-green-100 text-green-700
                                                @elseif($status === 'penyusutan') bg-red-100 text-red-700
                                                @elseif($status === 'sesuai') bg-blue-100 text-blue-700
                                                @else bg-zinc-100 text-zinc-500 @endif
                                            ">
                                            {{ ucfirst($status) ?: '-' }}
                                        </span>
                                    </div>
                                @endif

                                @if ($type !== 'opname')
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
                                @endif

                                {{-- Subtotal & Tombol Hapus Rata Kanan --}}
                                <div class="flex justify-end items-center mt-2 text-white gap-4">
                                    @if ($type !== 'opname')
                                        <div class="flex items-center gap-2 text-green-400">
                                            <i class="fas fa-money-bill-wave"></i>
                                            <span>
                                                Rp
                                                {{ number_format((float) ($row['quantity'] ?? 0) * (float) ($row['unit_price'] ?? 0), 0, ',', '.') }}
                                            </span>
                                        </div>
                                    @endif

                                    <button wire:click="removeItem({{ $i }})"
                                        class="w-8 h-8 flex items-center justify-center text-red-600 hover:text-white hover:bg-red-600 rounded-full transition"
                                        title="Hapus">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                    <button wire:click="addItem" class="text-blue-600 mt-2 hover:underline">+ Tambah
                        Barang</button>
                    {{-- Total --}}
                    @if ($type !== 'opname')
                        <div class="flex justify-end font-semibold text-lg mt-4">
                            Total: Rp {{ number_format($total, 0, ',', '.') }}
                        </div>
                    @endif
                </div>

                {{-- Actions --}}
                <div class="flex justify-end space-x-2 mt-6">
                    <button @click="open = false" class="px-4 py-2 bg-gray-300 rounded">Batal</button>
                    <button wire:click="save" wire:loading.attr="disabled" wire:target="save"
                        class="px-4 py-2 bg-blue-600 text-white rounded flex items-center gap-2">
                        <span wire:loading.remove wire:target="save">Simpan</span>
                        <svg wire:loading wire:target="save" class="w-5 h-5 animate-spin text-white" fill="none"
                            viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- Modal Detail --}}
        <div x-data="{ open: @entangle('isDetailOpen') }">
            <div x-show="open" class="fixed inset-0 bg-black/40  backdrop-blur-sm z-40"></div>

            <div x-show="open"
                class="fixed top-1/2 left-1/2 w-full max-w-2xl transform -translate-x-1/2 -translate-y-1/2 z-50 bg-white dark:bg-zinc-800 rounded p-6 shadow-lg">

                {{-- Header --}}
                <h2 class="text-xl font-bold mb-4">Detail Transaksi</h2>

                {{-- Informasi Umum --}}
                <div class="mb-2"><strong>Kode :</strong> {{ $detail['code'] ?? '' }}</div>
                <div class="mb-2"><strong>Tanggal :</strong> {{ $detail['date'] ?? '' }}</div>
                <div class="mb-2">
                    <strong>
                        @if (($detail['type'] ?? '') === 'retur_in' || ($detail['type'] ?? '') === 'retur_out')
                            Customer:
                        @elseif (($detail['type'] ?? '') === 'out')
                            Customer:
                        @elseif (($detail['type'] ?? '') === 'in')
                            Supplier:
                        @endif
                    </strong>

                    {{-- Tampilkan customer atau supplier sesuai tipe transaksi --}}
                    @if (($detail['type'] ?? '') === 'retur_in' || ($detail['type'] ?? '') === 'out')
                        {{ $detail['customer_name'] ?? '-' }}
                    @elseif (($detail['type'] ?? '') === 'retur_out')
                        {{ $detail['supplier'] ?? '-' }}
                    @elseif (($detail['type'] ?? '') === 'in')
                        {{ $detail['supplier'] ?? '-' }}
                    @endif
                </div>

                @if (($detail['type'] ?? '') === 'retur_in')
                    <div class="mb-2 text-green-600 font-medium">Tipe: Retur dari Customer</div>
                @elseif (($detail['type'] ?? '') === 'retur_out')
                    <div class="mb-2 text-yellow-600 font-medium">Tipe: Retur ke Supplier</div>
                @endif

                @if (($detail['type'] ?? '') === 'adjustment')
                    <div class="mb-2"><strong>Jenis Opname:</strong>
                        {{ $opnameTypes[$detail['opname_type']] ?? '-' }}</div>
                    <div class="mb-2"><strong>Alasan:</strong>
                        {{ $differenceReasons[$detail['difference_reason']] ?? '-' }}</div>
                @endif

                {{-- Item List --}}
                <div class="mb-4">
                    @foreach ($detail['items'] ?? [] as $item)
                        <div class="grid grid-cols-4 gap-4 mb-2 text-sm">
                            {{-- Nama Barang --}}
                            <div class="flex items-center">
                                <span class="font-medium">{{ $item['name'] }} ({{ $item['brand'] }})</span>
                            </div>

                            @if (($detail['type'] ?? '') === 'adjustment')
                                <div class="flex justify-end items-center">
                                    <span>{{ $item['system_stock'] }} {{ $item['unit_symbol'] }}</span>
                                </div>
                            @endif

                            {{-- Status & Selisih (khusus opname) --}}
                            @if (($detail['type'] ?? '') === 'adjustment')
                                <div class="flex justify-end items-center text-sm">
                                    <span class="mr-2">{{ ucfirst($item['status']) }}</span>
                                    <span>({{ $item['difference'] > 0 ? '+' : '' }}{{ $item['difference'] }})</span>
                                </div>
                            @endif

                            {{-- Quantity --}}
                            <div class="flex justify-end items-center">
                                <span>{{ $item['converted_qty'] }} {{ $item['unit_symbol'] }}</span>
                            </div>


                            @if (($detail['type'] ?? '') !== 'adjustment')
                                {{-- Harga --}}
                                <div class="flex justify-end items-center">
                                    <span>Rp {{ number_format($item['price'], 0, ',', '.') }}</span>
                                </div>

                                {{-- Subtotal --}}
                                <div class="flex justify-end items-center">
                                    <span>Rp {{ number_format($item['subtotal'], 0, ',', '.') }}</span>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
                @if (($detail['type'] ?? '') !== 'adjustment')
                    {{-- Total --}}
                    <div class="text-right font-bold">
                        Total: Rp {{ number_format($detail['total'] ?? 0, 0, ',', '.') }}
                    </div>
                @endif

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
                        <button
                            onclick="confirmRejectWithReason(
                                    'Ingin menolak transaksi ini?', 
                                    'Ya, Tolak!', 
                                    (reason) => @this.call('reject', {{ $detail['id'] }}, reason)
                                )"
                            class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                            Reject
                        </button>
                    </div>
                @endif

                {{-- Tombol Tutup --}}
                <div class="flex justify-end mt-6 space-x-3">
                    @if (!empty($detail['id']))
                        <button wire:click="exportDetailPdf({{ $detail['id'] }})"
                            class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                            <i class="fas fa-file-pdf"></i>
                        </button>
                    @endif

                    <button @click="open = false" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">
                        Tutup
                    </button>
                </div>
            </div>

            <div x-data="{ open: @entangle('isPaymentModalOpen') }">
                <!-- Backdrop -->
                <div x-show="open" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-40"></div>

                <!-- Modal -->
                <div x-show="open"
                    class="fixed top-1/2 left-1/2 w-full sm:max-w-xl md:max-w-2xl lg:max-w-3xl transform -translate-x-1/2 -translate-y-1/2
                     bg-white dark:bg-zinc-800 p-6 rounded shadow-lg z-50 transition-all overflow-y-auto max-h-[90vh]">

                    <h2 class="text-xl font-bold mb-6 text-gray-800 dark:text-white">Pembayaran Termin</h2>

                    <!-- Tanggal -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-white mb-1">
                            Tanggal Pembayaran
                        </label>
                        <input type="date" wire:model.live="payment_paid_at"
                            class="w-full border rounded p-2 dark:bg-zinc-700 dark:text-white dark:border-zinc-600" />
                    </div>



                    <!-- Pilih Termin -->
                    @if (!empty($schedules))
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-white mb-1">
                                Pilih Termin (opsional)
                            </label>
                            <select wire:model.live="selected_schedule_id"
                                class="w-full border rounded p-2 bg-white dark:bg-zinc-700 dark:text-white dark:border-zinc-600">
                                <option value="">-- Tanpa Termin --</option>
                                @foreach ($schedules as $s)
                                    <option value="{{ $s['id'] }}"
                                        @if ($s['is_paid']) disabled @endif>
                                        Termin {{ $loop->iteration }} - Rp
                                        {{ number_format($s['amount'], 0, ',', '.') }} -
                                        Jatuh Tempo: {{ $s['due_date'] }}
                                        {{ $s['is_paid'] ? '(Sudah Dibayar)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <!-- Nominal Pembayaran -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-white mb-1">
                            Nominal Pembayaran
                        </label>
                        <input type="number" wire:model.live="payment_amount" placeholder="Masukkan jumlah"
                            class="w-full border rounded p-2 dark:bg-zinc-700 dark:text-white dark:border-zinc-600" />
                        <small class="text-gray-500 dark:text-gray-400 block mt-1">* Nominal dalam Rupiah</small>
                        @error('payment_amount')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Metode Pembayaran -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-white mb-1">
                            Metode Pembayaran
                        </label>
                        <select wire:model.live="payment_method"
                            class="w-full border rounded p-2 bg-white dark:bg-zinc-700 dark:text-white dark:border-zinc-600">
                            <option value="">-- Pilih Metode --</option>
                            <option value="cash">Tunai</option>
                            <option value="transfer">Transfer Bank</option>
                            <option value="ewallet">E-Wallet</option>
                            <option value="giro">Giro</option>
                            <option value="other">Lainnya</option>
                        </select>
                        @error('payment_method')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    @if ($payment_method === 'transfer')
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-white mb-1">
                                Nomor Referensi Transfer
                            </label>
                            <input type="text" wire:model.live="reference_number"
                                class="w-full border rounded p-2 dark:bg-zinc-700 dark:text-white dark:border-zinc-600" />
                        </div>
                    @endif

                    <!-- Catatan -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-white mb-1">
                            Catatan
                        </label>
                        <textarea wire:model.live="payment_note"
                            class="w-full border rounded p-2 dark:bg-zinc-700 dark:text-white dark:border-zinc-600" rows="3"
                            placeholder="Contoh: Pembayaran via transfer..."></textarea>
                    </div>

                    <!-- Tombol -->
                    <div class="flex justify-end gap-2">
                        <button @click="open = false"
                            class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400 dark:bg-zinc-600 dark:text-white dark:hover:bg-zinc-500">
                            Batal
                        </button>
                        <button wire:click="savePayment"
                            class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                            Simpan
                        </button>
                    </div>
                </div>
            </div>
            <div x-data="{ open: @entangle('isPaymentDetailModalOpen') }">
                <div x-show="open" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-40"></div>

                <div x-show="open"
                    class="fixed top-1/2 left-1/2 w-full sm:max-w-xl transform -translate-x-1/2 -translate-y-1/2
                    bg-white dark:bg-zinc-800 p-6 rounded shadow-lg z-50 overflow-y-auto max-h-[90vh]">
                    <h2 class="text-xl font-bold mb-4 text-gray-800 dark:text-white">Detail Pembayaran</h2>

                    <div class="space-y-4 text-sm">
                        @forelse ($paymentDetails as $p)
                            <div class="border-b pb-2 dark:border-zinc-600">
                                <div><strong>Tanggal:</strong> {{ $p['date'] }}</div>
                                <div><strong>Nominal:</strong> Rp {{ number_format($p['amount'], 0, ',', '.') }}</div>
                                <div><strong>Metode:</strong> {{ ucfirst($p['method']) }}</div>
                                <div><strong>Referensi:</strong> {{ $p['ref'] }}</div>
                                <div><strong>Catatan:</strong> {{ $p['note'] ?? '-' }}</div>
                                <div><strong>Diinput oleh:</strong> {{ $p['by'] }}</div>
                            </div>
                        @empty
                            <div class="text-gray-500">Tidak ada data pembayaran ditemukan.</div>
                        @endforelse
                    </div>

                    <div class="flex justify-end mt-6">
                        <button @click="open = false"
                            class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>


        </div>

    </x-card>
</div>
