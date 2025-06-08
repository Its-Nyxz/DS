@php
    $labels = [
        'in' => 'Masuk',
        'out' => 'Keluar',
        'retur' => 'Retur',
        'opname' => 'Stock Opname',
    ];
@endphp

<x-card title="Transaksi Barang {{ $labels[$type] ?? ucfirst($type) }}">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 gap-3">
        <input type="text" wire:model.live="search" placeholder="Cari nama barang..."
            class="border px-4 py-2 rounded w-1/3 dark:bg-zinc-800 dark:text-white">
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
                    <th class="px-4 py-3 border">Supplier</th>
                    <th class="px-4 py-3 border">Item</th>
                    <th class="px-4 py-3 border">Total</th>
                    <th class="px-4 py-3 border">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($transactions as $tx)
                    <tr class="border-b dark:border-zinc-700">
                        <td class="px-4 py-2">{{ $tx->transaction_date ?? $tx->created_at->format('d/m/Y') }}</td>
                        <td class="px-4 py-2">{{ $tx->transaction_code }}</td>
                        <td class="px-4 py-2">{{ $tx->supplier->name ?? '-' }}</td>
                        <td class="px-4 py-2">{{ $tx->items->count() }}</td>
                        <td class="px-4 py-2">
                            Rp {{ number_format($tx->items->sum('subtotal'), 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-2 space-x-2">
                            <button wire:click="edit({{ $tx->id }})"
                                class="text-yellow-600 hover:text-white hover:bg-yellow-600 p-1 rounded" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>

                            <button
                                onclick="confirm('Hapus transaksi ini?') && @this.call('delete', {{ $tx->id }})"
                                class="text-red-600 hover:text-white hover:bg-red-600 p-1 rounded" title="Hapus">
                                <i class="fas fa-trash-alt"></i>
                            </button>

                            <button wire:click="showDetail({{ $tx->id }})"
                                class="text-blue-600 hover:text-white hover:bg-blue-600 p-1 rounded"
                                title="Lihat Detail">
                                <i class="fas fa-eye"></i>
                            </button>

                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-gray-500 py-4">Tidak ada transaksi ditemukan.</td>
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
                {{ $editingId ? 'Edit' : 'Tambah' }} Transaksi {{ ucfirst($type) }}
            </h2>

            <div class="mb-3">
                <label>Kode Transaksi</label>
                <input type="text" class="w-full border rounded p-2 bg-gray-100" wire:model="transaction_code"
                    disabled>
            </div>


            {{-- Supplier --}}
            @if (in_array($type, ['in', 'retur']))
                <div class="mb-3">
                    <label>Supplier</label>
                    <select wire:model.live="supplier_id" class="w-full border rounded p-2">
                        <option value="">-- Pilih Supplier --</option>
                        @foreach ($suppliers as $s)
                            <option value="{{ $s->id }}">{{ $s->name }}</option>
                        @endforeach
                    </select>
                    @error('supplier_id')
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
                    <div class="grid grid-cols-7 gap-2 items-start mb-3">
                        {{-- Barang --}}
                        <div class="col-span-3">
                            <select wire:model.live="items.{{ $i }}.item_supplier_id"
                                wire:change="setItemSupplier({{ $i }}, $event.target.value)"
                                class="w-full border rounded p-2">
                                <option value="">-- Pilih Barang --</option>
                                @foreach ($itemSuppliers as $is)
                                    <option value="{{ $is->id }}">
                                        {{ $is->item->name }} ({{ $is->item->brand->name }} /
                                        {{ $is->item->unit->name }})    
                                    </option>
                                @endforeach
                            </select>
                            @error("items.$i.item_supplier_id")
                                <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Quantity --}}
                        <div>
                            <input type="number" wire:model.live="items.{{ $i }}.quantity"
                                class="w-full border rounded p-2" min="1">

                            @error("items.$i.quantity")
                                <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>


                        {{-- Harga --}}
                        <div>
                            <input type="number" wire:model.live="items.{{ $i }}.unit_price"
                                class="w-full border rounded p-2" step="0.01">
                        </div>

                        {{-- Subtotal + Tombol Hapus --}}
                        <div
                            class="col-span-2 flex items-center justify-end gap-3 text-gray-800 dark:text-white font-medium mt-2">
                            {{-- Subtotal --}}
                            <div class="flex items-center gap-1">
                                <i class="fas fa-money-bill-wave text-green-500"></i>
                                <span>
                                    Rp
                                    {{ number_format((float) ($row['quantity'] ?? 0) * (float) ($row['unit_price'] ?? 0), 0, ',', '.') }}
                                </span>
                            </div>

                            {{-- Tombol Hapus --}}
                            <button wire:click="removeItem({{ $i }})"
                                class="w-8 h-8 flex items-center justify-center text-red-600 hover:text-white hover:bg-red-600 rounded-full transition"
                                title="Hapus">
                                <i class="fas fa-trash-alt"></i>
                            </button>
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
            <h2 class="text-xl font-bold mb-4">Detail Transaksi</h2>

            <div class="mb-2"><strong>Kode:</strong> {{ $detail['code'] ?? '' }}</div>
            <div class="mb-2"><strong>Tanggal:</strong> {{ $detail['date'] ?? '' }}</div>
            <div class="mb-2"><strong>Supplier:</strong> {{ $detail['supplier'] ?? '' }}</div>

            <div class="my-4">
                <table class="w-full text-sm border">
                    <thead class="bg-gray-100 dark:bg-zinc-700">
                        <tr>
                            <th class="border px-2 py-1">Nama Barang</th>
                            <th class="border px-2 py-1 text-right">Qty</th>
                            <th class="border px-2 py-1 text-right">Harga</th>
                            <th class="border px-2 py-1 text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($detail['items'] ?? [] as $item)
                            <tr>
                                <td class="border px-2 py-1">{{ $item['name'] }}</td>
                                <td class="border px-2 py-1 text-right">{{ $item['qty'] }}</td>
                                <td class="border px-2 py-1 text-right">
                                    Rp {{ number_format($item['price'], 0, ',', '.') }}
                                </td>
                                <td class="border px-2 py-1 text-right">
                                    Rp {{ number_format($item['subtotal'], 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="text-right font-bold">
                Total: Rp {{ number_format($detail['total'] ?? 0, 0, ',', '.') }}
            </div>

            <div class="mt-2 text-sm">
                <strong>Catatan:</strong> {{ $detail['note'] ?? '-' }}
            </div>

            <div class="flex justify-end mt-6">
                <button @click="open = false" class="px-4 py-2 bg-gray-300 rounded">Tutup</button>
            </div>
        </div>
    </div>

</x-card>
