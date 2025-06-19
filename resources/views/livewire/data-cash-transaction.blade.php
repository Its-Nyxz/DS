@php
    $labels = [
        'utang' => 'Kas Utang',
        'piutang' => 'Kas Piutang',
        'arus' => 'Semua Arus Kas',
    ];

    $fieldLabels = [
        'utang' => 'Utang',
        'piutang' => 'Piutang',
        'arus' => 'Kas',
    ];
@endphp

<div>
    <x-card title="{{ $labels[$type] ?? 'Data Kas' }}">
        {{-- Filter & Tombol --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 gap-3">
            <div class="flex flex-wrap gap-2 w-full md:w-auto">
                <input type="text" wire:model.live="search" placeholder="Cari..."
                    class="border px-4 py-2 rounded dark:bg-zinc-800 dark:text-white w-full md:w-64">

                <select wire:model.live="orderBy"
                    class="border rounded p-2 dark:bg-zinc-800 dark:text-white dark:border-zinc-600">
                    <option value="transaction_date">Tanggal</option>
                    <option value="transaction_type">Jenis</option>
                    <option value="amount">Jumlah</option>
                </select>

                <select wire:model.live="orderDirection"
                    class="border rounded p-2 dark:bg-zinc-800 dark:text-white dark:border-zinc-600">
                    <option value="asc">Naik</option>
                    <option value="desc">Turun</option>
                </select>

                {{-- Filter Tanggal --}}
                <input type="date" wire:model.live="startDate"
                    class="border px-2 py-1 rounded dark:bg-zinc-800 dark:text-white">
                <input type="date" wire:model.live="endDate"
                    class="border px-2 py-1 rounded dark:bg-zinc-800 dark:text-white">

                <select wire:model.live="paymentStatus"
                    class="border rounded p-2 dark:bg-zinc-800 dark:text-white dark:border-zinc-600">
                    <option value="">Semua Status</option>
                    <option value="paid">Lunas</option>
                    <option value="unpaid">Hutang</option>
                </select>
            </div>

            {{-- Tombol --}}
            <div class="flex gap-2 mt-2 sm:mt-0">
                <button wire:click="exportPdf"
                    class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 flex items-center gap-1">
                    <i class="fas fa-file-pdf"></i>
                </button>
                @can('create-transaksi-kas')
                    <button wire:click="create" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        + Tambah {{ $fieldLabels[$type] ?? 'Transaksi' }}
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
                        <th class="px-4 py-3 border">Referensi</th>
                        <th class="px-4 py-3 border">Jenis</th>
                        <th class="px-4 py-3 border">Metode</th>
                        <th class="px-4 py-3 border text-right">Tagihan</th>
                        <th class="px-4 py-3 border text-right">Dibayar</th>
                        <th class="px-4 py-3 border text-right">Sisa</th>
                        <th class="px-4 py-3 border">Catatan</th>
                        <th class="px-4 py-3 border text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transactions as $tx)
                        <tr class="border-b dark:border-zinc-700">
                            <td class="px-4 py-2">
                                {{ \Carbon\Carbon::parse($tx->transaction_date)->format('d/m/Y') }}
                            </td>
                            <td class="px-4 py-2">{{ $tx->reference_number ?? '-' }}</td>
                            <td class="px-4 py-2">{{ ucfirst($tx->transaction_type) }}</td>
                            <td class="px-4 py-2 text-center">
                                @php
                                    $methodColor = [
                                        'cash' => 'bg-green-100 text-green-800',
                                        'term' => 'bg-yellow-100 text-yellow-800',
                                    ];
                                @endphp

                                <span
                                    class="px-2 py-1 rounded text-xs font-semibold {{ $methodColor[$tx->payment_method] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ ucfirst($tx->payment_method === 'term' ? 'Cicilan' : $tx->payment_method) }}
                                </span>
                            </td>
                            @php
                                $paid = $tx->stockTransaction?->cashTransactions->sum('amount') ?? 0;
                                $total = $tx->stockTransaction?->total_amount ?? $tx->amount; // fallback jika tidak ada relasi
                                $remaining = $total - $paid;

                                $isUtang = $type === 'utang';

                                // Warna berdasarkan type
                                $classTagihan = $isUtang
                                    ? 'text-red-600 font-semibold'
                                    : 'text-yellow-600 font-semibold';
                                $classDibayar = $isUtang
                                    ? 'text-yellow-600 font-semibold'
                                    : 'text-green-600 font-semibold';
                                $classSisa = $isUtang ? 'text-green-600 font-semibold' : 'text-red-600 font-semibold';
                            @endphp

                            <td class="px-4 py-2 text-right {{ $classTagihan }}">
                                Rp {{ number_format($total, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-2 text-right {{ $classDibayar }}">
                                Rp {{ number_format($paid, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-2 text-right {{ $classSisa }}">
                                Rp {{ number_format($remaining, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-2">{{ $tx->note ?? '-' }}</td>
                            <td class="px-4 py-2 text-center flex justify-center gap-2 items-center">
                                @if ($tx->transaction_type === 'stock' && !$tx->stockTransaction?->is_fully_paid)
                                    <button wire:click="openPaymentModal({{ $tx->stock_transaction_id }})"
                                        class="text-blue-600 bg-blue-100 hover:bg-blue-200 px-2 py-1 text-xs rounded">
                                        Bayar
                                    </button>
                                @endif

                                {{-- Status Lunas / Hutang --}}
                                @if ($tx->transaction_type === 'stock' && $tx->stockTransaction?->is_fully_paid)
                                    <span
                                        class="text-green-600 text-xs font-semibold bg-green-100 px-2 py-1 rounded">Lunas</span>
                                @elseif ($tx->transaction_type === 'stock' && !$tx->stockTransaction?->is_fully_paid)
                                    <span
                                        class="text-red-600 text-xs font-semibold bg-red-100 px-2 py-1 rounded">Hutang</span>
                                @else
                                    <span
                                        class="text-gray-600 text-xs font-semibold bg-gray-100 px-2 py-1 rounded">-</span>
                                @endif

                                {{-- Tombol Edit --}}
                                {{-- @can('edit-transaksi-kas') --}}
                                <button wire:click="edit({{ $tx->id }})"
                                    class="text-yellow-600 hover:text-white hover:bg-yellow-600 p-1 rounded"
                                    title="Edit">
                                    <i class="fas fa-eye"></i>
                                </button>
                                {{-- @endcan --}}

                                {{-- Tombol Hapus --}}
                                {{-- @can('delete-transaksi-kas') --}}
                                {{-- <button
                                    onclick="confirmAlert('Hapus transaksi?', 'Ya, hapus!', () => @this.call('delete', {{ $tx->id }}))"
                                    class="text-red-600 hover:text-white hover:bg-red-600 p-1 rounded" title="Hapus">
                                    <i class="fas fa-trash-alt"></i>
                                </button> --}}
                                {{-- @endcan --}}
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-gray-500 py-4">Tidak ada transaksi ditemukan.
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

        {{-- Modal Pembayaran --}}
        <div x-data="{ open: @entangle('paymentModal') }">
            <div x-show="open" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-40"></div>

            <div x-show="open"
                class="fixed top-1/2 left-1/2 w-full max-w-lg transform -translate-x-1/2 -translate-y-1/2 z-50
        bg-white dark:bg-zinc-800 rounded p-6 shadow-lg overflow-y-auto max-h-[90vh]">

                <h2 class="text-xl font-bold mb-4 text-zinc-800 dark:text-white">Pembayaran Utang/Piutang</h2>

                {{-- Tanggal --}}
                <div class="mb-4">
                    <label class="block text-sm mb-1 dark:text-white">Tanggal Pembayaran</label>
                    <input type="date" wire:model.live="paymentDate"
                        class="w-full border rounded p-2 bg-white dark:bg-zinc-800 dark:text-white" />
                    @error('paymentDate')
                        <div class="text-sm text-red-600 mt-1">{{ $message }}</div>
                    @enderror
                </div>


                <div class="mb-4 space-y-2 text-sm">
                    <div>
                        <label class="block text-sm mb-1 dark:text-white">Total Tagihan</label>
                        <input type="text"
                            class="w-full border rounded p-2 bg-gray-100 dark:bg-zinc-700 dark:text-white"
                            value="Rp {{ number_format($paymentTotal, 0, ',', '.') }}" disabled />
                    </div>
                    <div>
                        <label class="block text-sm mb-1 dark:text-white">Sudah Dibayar</label>
                        <input type="text"
                            class="w-full border rounded p-2 bg-gray-100 dark:bg-zinc-700 dark:text-white"
                            value="Rp {{ number_format($paymentPaid, 0, ',', '.') }}" disabled />
                    </div>
                    <div>
                        <label class="block text-sm mb-1 dark:text-white">Sisa Tagihan</label>
                        <input type="text"
                            class="w-full border rounded p-2 bg-gray-100 dark:bg-zinc-700 dark:text-white"
                            value="Rp {{ number_format($paymentRemaining, 0, ',', '.') }}" disabled />
                    </div>
                </div>

                {{-- Jumlah Pembayaran --}}
                <div class="mb-4">
                    <label class="block text-sm mb-1 dark:text-white">Jumlah</label>
                    <input type="number" wire:model.live="paymentAmount"
                        class="w-full border rounded p-2 bg-white dark:bg-zinc-800 dark:text-white" />
                    @error('paymentAmount')
                        <div class="text-sm text-red-600 mt-1">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Catatan --}}
                <div class="mb-4">
                    <label class="block text-sm mb-1 dark:text-white">Catatan (Opsional)</label>
                    <textarea wire:model.live="paymentNote" rows="2"
                        class="w-full border rounded p-2 bg-white dark:bg-zinc-800 dark:text-white"></textarea>
                </div>

                {{-- Riwayat Pembayaran --}}
                @if ($paymentHistory && count($paymentHistory))
                    <div class="mb-4">
                        <h3 class="font-semibold text-sm mb-2 dark:text-white">Riwayat Pembayaran:</h3>
                        <ul class="space-y-1 text-sm text-zinc-700 dark:text-zinc-300 max-h-40 overflow-y-auto">
                            @foreach ($paymentHistory as $history)
                                <li class="flex justify-between border-b pb-1">
                                    <span>{{ \Carbon\Carbon::parse($history->transaction_date)->format('d/m/Y') }}</span>
                                    <span>Rp {{ number_format($history->amount, 0, ',', '.') }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Action Buttons --}}
                <div class="flex justify-end space-x-2 mt-6">
                    <button @click="open = false" class="px-4 py-2 bg-gray-300 rounded">Batal</button>
                    <button wire:click="savePayment" wire:loading.attr="disabled"
                        class="px-4 py-2 bg-blue-600 text-white rounded flex items-center gap-2">
                        <span wire:loading.remove wire:target="savePayment">Simpan</span>
                        <svg wire:loading wire:target="savePayment" class="w-5 h-5 animate-spin text-white"
                            fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

    </x-card>
</div>
