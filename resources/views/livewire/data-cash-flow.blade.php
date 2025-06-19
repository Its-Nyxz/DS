<x-card title="Arus Kas ">
    {{-- Filter & Export --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 gap-3">
        <div class="flex flex-wrap gap-2 w-full md:w-auto">
            <input type="text" wire:model.live="search" placeholder="Cari keterangan atau referensi..."
                class="border px-4 py-2 rounded dark:bg-zinc-800 dark:text-white w-full md:w-64">

            <input type="date" wire:model.live="startDate"
                class="border px-2 py-1 rounded dark:bg-zinc-800 dark:text-white">
            <input type="date" wire:model.live="endDate"
                class="border px-2 py-1 rounded dark:bg-zinc-800 dark:text-white">
        </div>

        <div class="flex gap-2">
            <button wire:click="exportPdf"
                class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 flex items-center gap-1">
                <i class="fas fa-file-pdf"></i>
            </button>
        </div>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white dark:bg-zinc-800 text-sm">
            <thead class="bg-gray-100 dark:bg-zinc-700 text-gray-700 dark:text-gray-300">
                <tr>
                    <th class="px-4 py-3 border text-left">Tanggal</th>
                    <th class="px-4 py-3 border text-left">Transaksi</th>
                    <th class="px-4 py-3 border text-left">Keterangan</th>
                    <th class="px-4 py-3 border text-right">Debit</th>
                    <th class="px-4 py-3 border text-right">Kredit</th>
                    <th class="px-4 py-3 border text-right">Saldo</th>
                    <th class="px-4 py-3 border text-right"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($transactions as $tx)
                    <tr
                        class="border-b dark:border-zinc-700 @if ($tx['tanggal'] === 'Total') font-bold bg-gray-50 dark:bg-zinc-700 @endif">
                        <td class="px-4 py-2">{{ $tx['tanggal'] }}</td>
                        <td class="px-4 py-2">
                            {{ $tx['transaction_code'] ?? '' }}
                        </td>
                        <td class="px-4 py-2">{{ $tx['keterangan'] }}</td>
                        <td class="px-4 py-2 text-right text-green-600">
                            Rp {{ number_format($tx['debit'], 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-2 text-right text-red-600">
                            Rp {{ number_format($tx['kredit'], 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-2 text-right font-semibold">
                            Rp {{ number_format($tx['saldo'] ?? 0, 0, ',', '.') }}
                        </td>
                        @if (isset($tx['id']))
                            <td class="text-right">
                                <button wire:click="showCashDetail({{ $tx['id'] }})"
                                    class="text-indigo-600 hover:text-white hover:bg-indigo-600 p-1 rounded"
                                    title="Lihat Detail">
                                    <i class="fas fa-info-circle"></i>
                                </button>
                            </td>
                        @else
                            <td></td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-gray-500 py-4">Tidak ada data ditemukan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Modal Lihat Detail Kas --}}
    <div x-data="{ open: @entangle('showDetailModal') }">
        <div x-show="open" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-40"></div>

        <div x-show="open"
            class="fixed top-1/2 left-1/2 w-full max-w-2xl transform -translate-x-1/2 -translate-y-1/2 z-50
bg-white dark:bg-zinc-800 rounded p-6 shadow-lg overflow-y-auto max-h-[90vh]">

            <h2 class="text-lg font-semibold mb-4 text-zinc-800 dark:text-white">
                Detail Transaksi Kas
            </h2>

            @if ($transactionDetail)
                <div class="space-y-2 text-sm dark:text-white mb-6">
                    <div class="flex justify-between">
                        <span><strong>Tanggal:</strong></span>
                        <span>{{ \Carbon\Carbon::parse($transactionDetail['tanggal'])->format('d/m/Y H:i') }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span><strong>Nomor Referensi:</strong></span>
                        <span>{{ $transactionDetail['reference'] }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span><strong>Metode Pembayaran:</strong></span>
                        <span>{{ ucfirst($transactionDetail['metode'] === 'term' ? 'Cicilan' : $transactionDetail['metode']) }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span><strong>Catatan:</strong></span>
                        <span>{{ $transactionDetail['note'] ?? '-' }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span><strong>Total Tagihan:</strong></span>
                        <span class="text-red-600 font-semibold">Rp
                            {{ number_format($transactionDetail['tagihan'], 0, ',', '.') }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span><strong>Sudah Dibayar:</strong></span>
                        <span class="text-green-600 font-semibold">Rp
                            {{ number_format($transactionDetail['dibayar'], 0, ',', '.') }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span><strong>Sisa:</strong></span>
                        <span class="text-yellow-600 font-semibold">Rp
                            {{ number_format($transactionDetail['sisa'], 0, ',', '.') }}</span>
                    </div>

                    {{-- Transaksi Stok --}}
                    @if (!empty($transactionDetail['transaction_code']))
                        <hr class="my-3 border-t dark:border-zinc-600">
                        <div class="font-semibold text-sm mt-2">Terkait Transaksi Stok</div>
                        <div class="flex justify-between">
                            <span>Kode Transaksi</span>
                            <span>{{ $transactionDetail['transaction_code'] }}</span>
                        </div>
                    @endif
                </div>

                @if (!empty($transactionDetail['pembayaran']) && count($transactionDetail['pembayaran']))
                    <div class="mt-4">
                        <h3 class="text-sm font-semibold mb-2 dark:text-white">Riwayat Pembayaran:</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm bg-white dark:bg-zinc-700 rounded shadow">
                                <thead class="bg-gray-100 dark:bg-zinc-600 dark:text-white">
                                    <tr>
                                        <th class="px-4 py-2 text-left">Nomor Referensi</th>
                                        <th class="px-4 py-2 text-left">Tanggal</th>
                                        <th class="px-4 py-2 text-right">Jumlah</th>
                                        <th class="px-4 py-2">Catatan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($transactionDetail['pembayaran'] as $pay)
                                        <tr class="border-t dark:border-zinc-600">
                                            <td class="px-4 py-2">{{ $pay->reference_number ?? '-' }}</td>
                                            <td class="px-4 py-2">
                                                {{ \Carbon\Carbon::parse($pay->transaction_date)->format('d/m/Y') }}
                                            </td>
                                            <td class="px-4 py-2 text-right text-green-600">
                                                Rp {{ number_format($pay->amount, 0, ',', '.') }}
                                            </td>
                                            <td class="px-4 py-2">{{ $pay->note ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            @else
                <p class="text-sm text-gray-500">Tidak ada data transaksi yang dipilih.</p>
            @endif

            <div class="mt-6 flex justify-end space-x-2">
                <button @click="open = false" class="px-4 py-2 bg-gray-300 rounded">Tutup</button>
            </div>
        </div>
    </div>


</x-card>
