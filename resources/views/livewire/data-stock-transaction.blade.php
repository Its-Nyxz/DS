@php
    $labels = [
        'in' => 'Transaksi Pembelian',
        'out' => 'Transaksi Penjualan',
        'retur' => 'Transaksi Retur',
        'opname' => 'Stock Opname',
    ];

    $field = [
        'in' => 'Pembelian',
        'out' => 'Penjualan',
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

                @can('create-transaksi')
                    <button wire:click="create" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        + Tambah {{ $field[$type] ?? ucfirst($type) }}
                    </button>
                @endcan
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
                                    <div x-data="{ showOptions{{ $tx->id }}: false }" class="relative">
                                        @if ($tx->is_approved)
                                            <span
                                                class="text-green-600 text-xs font-semibold bg-green-100 px-2 py-1 rounded">Approved</span>
                                        @else
                                            @can('approve-transaksi')
                                                <button
                                                    @click="showOptions{{ $tx->id }} = !showOptions{{ $tx->id }}"
                                                    class="text-yellow-600 text-xs font-semibold bg-yellow-100 px-2 py-1 rounded hover:bg-yellow-200 transition">
                                                    Pending
                                                </button>

                                                <div x-show="showOptions{{ $tx->id }}"
                                                    @click.away="showOptions{{ $tx->id }} = false"
                                                    class="absolute z-10 bg-white border dark:bg-zinc-700 dark:border-zinc-600 shadow-lg rounded mt-1 w-28 text-sm">
                                                    <button wire:click="approve({{ $tx->id }})"
                                                        class="block w-full px-3 py-1 text-green-600 hover:bg-green-100 dark:hover:bg-zinc-600 text-left">
                                                        Approve
                                                    </button>
                                                    <button
                                                        onclick="confirmRejectWithReason(
                                                                                'Tolak transaksi {{ $tx->transaction_code }}?',
                                                                                'Ya, Tolak!',
                                                                                (reason)
=> @this.call('reject', {{ $tx->id }}, reason)
                                                                            )"
                                                        class="block w-full px-3 py-1 text-red-600 hover:bg-red-100 dark:hover:bg-zinc-600 text-left">
                                                        Reject
                                                    </button>
                                                </div>
                                            @else
                                                <span
                                                    class="text-yellow-600 text-xs font-semibold bg-yellow-100 px-2 py-1 rounded">Pending</span>
                                            @endcan
                                        @endif
                                    </div>
                                @endif
                                @if (in_array($type, ['in', 'out']))
                                    @can('termin-transaksi')
                                        {{-- @if ($tx->is_fully_paid)
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
                                        @endif --}}

                                        @if ($tx->is_fully_paid)
                                            <span class="text-blue-600 text-xs font-semibold bg-blue-100 px-2 py-1 rounded">
                                                Lunas
                                            </span>
                                        @else
                                            <span class="text-red-600 text-xs font-semibold bg-red-100 px-2 py-1 rounded">
                                                Hutang
                                            </span>
                                        @endif
                                    @else
                                        @if ($tx->is_fully_paid)
                                            <span class="text-blue-600 text-xs font-semibold bg-blue-100 px-2 py-1 rounded">
                                                Lunas
                                            </span>
                                        @else
                                            <span class="text-red-600 text-xs font-semibold bg-red-100 px-2 py-1 rounded">
                                                Hutang
                                            </span>
                                        @endif
                                    @endcan
                                @endif

                                {{-- Tombol Edit --}}
                                @can('edit-transaksi')
                                    <button wire:click="edit({{ $tx->id }})"
                                        class="text-yellow-600 hover:text-white hover:bg-yellow-600 p-1 rounded"
                                        title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                @endcan

                                {{-- Tombol Hapus --}}
                                @can('delete-transaksi')
                                    <button
                                        onclick="confirmAlert('Hapus transaksi {{ $tx->transaction_code }}?', 'Ya, hapus!', () => @this.call('delete', {{ $tx->id }}))"
                                        class="text-red-600 hover:text-white hover:bg-red-600 p-1 rounded" title="Hapus">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                @endcan

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
                        <label class="block mb-1 text-sm dark:text-white">Jenis Pengembalian</label>
                        <p class="text-xs text-gray-500 mt-1">
                            Pilih <strong>Retur Uang</strong> jika mengembalikan dana ke customer, atau <strong>Retur
                                Barang</strong> jika menukar barang.
                        </p>
                        <select wire:model.live="return_type"
                            class="w-full border rounded p-2 bg-white dark:bg-zinc-800 dark:text-white dark:border-zinc-600">
                            <option value="">Pilih Jenis</option>
                            <option value="uang">Retur Uang</option>
                            <option value="barang">Retur Barang</option>
                        </select>
                        @error('return_type')
                            <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>
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
                            {{-- <label class="block mb-1 text-sm dark:text-white">Supplier</label>
                            <select wire:model.live="supplier_id"
                                wire:change="tryAutoFillItemsFromPreviousTransaction"
                                class="w-full border rounded p-2 dark:bg-zinc-800 dark:text-white dark:border-zinc-600">
                                <option value="">Pilih Supplier</option>
                                @foreach ($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                @endforeach
                            </select>
                            @error('supplier_id')
                                <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                            @enderror --}}
                            <div x-data x-init="window.initSelect = () => {
                                $('#supplier-select').select2().on('change', function(e) {
                                    @this.set('supplier_id', $(this).val());
                                    @this.call('tryAutoFillItemsFromPreviousTransaction');
                                });
                            };
                            initSelect();
                            Livewire.on('reinitSelect', () => {
                                $('#supplier-select').select2();
                            });">
                                <label class="block mb-1 text-sm dark:text-white">Supplier</label>
                                <select id="supplier-select"
                                    class="w-full border rounded p-2 dark:bg-zinc-800 dark:text-white dark:border-zinc-600">
                                    <option value="">Pilih Supplier</option>
                                    @foreach ($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}"
                                            @if ($supplier_id == $supplier->id) selected @endif>
                                            {{ $supplier->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('supplier_id')
                                    <span class="text-red-500 text-xs">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    @elseif($subtype === 'retur_in')
                        {{-- Input Nama Customer --}}
                        <div class="mb-3">
                            {{-- <label class="block mb-1 text-sm dark:text-white">Customer</label> --}}
                            {{-- <select wire:model.live="customer_id"
                                wire:change="tryAutoFillItemsFromPreviousTransaction"
                                class="w-full border rounded p-2 dark:bg-zinc-800 dark:text-white dark:border-zinc-600">
                                <option value="">Pilih Customer</option>
                                @foreach ($customers as $customer)
                                    <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                @endforeach
                            </select> --}}
                            {{-- @error('customer_id')
                                <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                            @enderror --}}
                            <div x-data x-init="window.initSelect = () => {
                                $('#customer-select').select2().on('change', function(e) {
                                    @this.set('customer_id', $(this).val());
                                    @this.call('tryAutoFillItemsFromPreviousTransaction');
                                });
                            };
                            initSelect();
                            Livewire.on('reinitSelect', () => {
                                $('#customer-select').select2();
                            });">
                                <label class="block mb-1 text-sm dark:text-white">Customer</label>
                                <select id="customer-select"
                                    class="w-full border rounded p-2 dark:bg-zinc-800 dark:text-white dark:border-zinc-600">
                                    <option value="">Pilih Customer</option>
                                    @foreach ($customers as $customer)
                                        <option value="{{ $customer->id }}"
                                            @if ($customer_id == $customer->id) selected @endif>
                                            {{ $customer->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('customer_id')
                                    <span class="text-red-500 text-xs">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    @endif
                @elseif ($type === 'in')
                    {{-- Supplier untuk Transaksi Masuk --}}
                    <div class="mb-3">
                        <label class="block mb-1 text-sm dark:text-white">Supplier</label>
                        <input type="text" wire:model.live="supplier_name"
                            wire:input="fetchSuggestions('supplier', $event.target.value)"
                            wire:blur="hideSuggestions('supplier')"
                            class="w-full border rounded px-3 py-2 dark:bg-zinc-800 dark:text-white"
                            placeholder="Cari Supplier..." autocomplete="off">

                        @if ($suggestions['supplier'])
                            <ul
                                class="absolute z-20 w-full bg-white border border-gray-300 rounded mt-1 max-h-60 overflow-auto max-w-full sm:max-w-[90%] md:max-w-[80%] lg:max-w-[70%]">
                                @foreach ($suggestions['supplier'] as $suggestion)
                                    <li wire:click="selectSuggestion('supplier', '{{ $suggestion }}')"
                                        class="px-4 py-2 bg-white dark:bg-zinc-800 text-gray-800 dark:text-white hover:bg-gray-100 dark:hover:bg-zinc-700 hover:text-black dark:hover:text-gray-200 cursor-pointer transition duration-150">
                                        {{ $suggestion }}
                                    </li>
                                @endforeach
                            </ul>
                        @endif

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

                @if ($type === 'in' || $type === 'out')
                    <div class="mb-3">
                        <label class="block mb-1 text-sm dark:text-white">Tipe Pembayaran</label>
                        <select wire:model.live="payment_type"
                            class="w-full border rounded p-2 bg-white text-gray-800 border-gray-300 dark:bg-zinc-800 dark:text-white dark:border-zinc-700 overflow-y-auto max-h-60">
                            <option value="cash">Cash</option>
                            <option value="term">Cicilan</option>
                        </select>
                        @error('payment_type')
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
                                    @if ($type === 'in' || ($type === 'retur' && $subtype === 'retur_out'))
                                        {{-- IN / RETUR OUT: pilih dari itemSuppliers --}}
                                        <select wire:model.live="items.{{ $i }}.item_supplier_id"
                                            wire:change="setItemSupplier({{ $i }}, $event.target.value)"
                                            class="w-full border rounded p-2 bg-white text-gray-800 border-gray-300 dark:bg-zinc-800 dark:text-white dark:border-zinc-700 overflow-y-auto max-h-60">
                                            <option value="">-- Pilih Barang (Supplier) --</option>
                                            @foreach ($itemSuppliers as $is)
                                                <option value="{{ $is->id }}">
                                                    {{ $is->item->name }} {{ $is->item->brand->name ?? '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error("items.$i.item_supplier_id")
                                            <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                                        @enderror
                                    @else
                                        {{-- OUT / RETUR IN: pilih dari master Items --}}
                                        <select wire:model.live="items.{{ $i }}.item_id"
                                            wire:change="setItemForOut({{ $i }}, $event.target.value)"
                                            class="w-full border rounded p-2 bg-white text-gray-800 border-gray-300 dark:bg-zinc-800 dark:text-white dark:border-zinc-700 overflow-y-auto max-h-60">
                                            <option value="">-- Pilih Barang --</option>
                                            @foreach ($itemsMaster as $im)
                                                <option value="{{ $im->id }}">
                                                    {{ $im->name }} {{ $im->brand->name ?? '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error("items.$i.item_id")
                                            <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                                        @enderror
                                    @endif
                                </div>

                                {{-- Konversi Satuan (tampilkan kalau OUT, OPNAME, atau RETUR IN) --}}
                                @if (in_array($type, ['out', 'opname']) || ($type === 'retur' && $subtype === 'retur_in'))
                                    <div class="md:col-span-2">
                                        <select wire:model.live="items.{{ $i }}.selected_unit_id"
                                            class="w-full border rounded p-2 bg-white text-gray-800 border-gray-300 dark:bg-zinc-800 dark:text-white dark:border-zinc-700 overflow-y-auto max-h-60">
                                            <option value="">Konversi Satuan</option>
                                            @foreach ($conversions as $conv)
                                                <option value="{{ (int) $conv['to_unit_id'] }}"
                                                    {{ $conv['to_unit_id'] == ($row['selected_unit_id'] ?? null) ? 'selected' : '' }}>
                                                    {{ $conv['to_unit']['symbol'] ?? '-' }} — Faktor:
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

                                    <span class="absolute top-2 right-2 text-sm text-zinc-400">
                                        {{ $row['unit_symbol'] ?? '-' }}
                                    </span>
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

                                        <span class="absolute top-2 right-2 text-sm text-zinc-400">
                                            {{ $row['unit_symbol'] ?? '-' }}
                                        </span>
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
                {{-- @if (!empty($detail['id']) && !$detail['is_approved'] && ($detail['type'] ?? '') === 'in' && auth()->user()->hasRole('pemilik'))
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
                @endif --}}

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
        </div>

    </x-card>
</div>
