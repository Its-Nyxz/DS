<x-card title="Arus Kas">
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
            <div class="flex justify-end">
                <button wire:click="openForm" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    + Tambah
                </button>
            </div>

            <button wire:click="exportPdf"
                class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 flex items-center gap-1">
                <i class="fas fa-file-pdf"></i>
            </button>
        </div>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto max-h-[35rem] overflow-y-auto">
        <table class="min-w-full bg-white dark:bg-zinc-800 text-sm">
            <thead class="bg-gray-100 dark:bg-zinc-700 text-gray-700 dark:text-gray-300">
                <tr>
                    <th class="px-4 py-3 border text-left">Tanggal</th>
                    <th class="px-4 py-3 border text-left">No. Refrensi</th>
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
                        class="border-b dark:border-zinc-700 
                        @if ($tx['tanggal'] === 'Total') font-bold bg-gray-50 dark:bg-zinc-700 
                        @elseif ($tx['tanggal'] === 'Saldo Awal') italic bg-yellow-50 dark:bg-zinc-900 @endif">
                        <td class="px-4 py-2">{{ $tx['tanggal'] }}</td>
                        <td class="px-4 py-2">
                            {{ $tx['reference_number'] ?? '' }}
                        </td>
                        <td class="px-4 py-2">
                            {{ $tx['keterangan'] }}
                        </td>
                        <td class="px-4 py-2 text-right text-green-600">
                            Rp {{ number_format($tx['debit'], 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-2 text-right text-red-600">
                            Rp {{ number_format($tx['kredit'], 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-2 text-right font-semibold">
                            Rp {{ number_format($tx['saldo'] ?? 0, 0, ',', '.') }}
                        </td>
                        @if (isset($tx['id']) && $tx['tanggal'] !== 'Total' && $tx['tanggal'] !== 'Saldo Awal')
                            <td class="text-right">
                                <button wire:click="openForm({{ $tx['id'] }})"
                                    class="text-orange-600 hover:text-white hover:bg-orange-600 p-1 rounded"
                                    title="Edit Transaksi">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <!-- Delete Button -->
                                <button type="button"
                                    onclick="confirmAlert('Yakin ingin menghapus data ini?', 'Ya, hapus!', () => @this.call('delete', {{ $tx['id'] }}))"
                                    class="px-2 py-1 text-sm text-red-600 hover:text-white hover:bg-red-600 rounded transition duration-150"
                                    title="Hapus">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
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

    <div x-data="{ open: @entangle('showFormModal') }">
        <div x-show="open" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-40"></div>

        <div x-show="open" x-transition
            class="fixed top-1/2 left-1/2 w-full max-w-xl transform -translate-x-1/2 -translate-y-1/2 z-50
               bg-white dark:bg-zinc-800 p-6 rounded shadow-lg">

            <h2 class="text-lg font-semibold mb-4 text-zinc-800 dark:text-white">
                {{ $form['id'] ? 'Edit Transaksi Kas' : 'Tambah Transaksi Kas' }}
            </h2>

            <div class="space-y-3 text-sm">
                <div>
                    <label class="block mb-1">Jenis Transaksi</label>
                    <select wire:model.live="form.transaction_type"
                        class="w-full border border-zinc-300 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 px-3 py-2 rounded">
                        <option value="">-- Pilih --</option>
                        {{-- <option value="stock">Stok</option> --}}
                        <option value="expense">Pengeluaran</option>
                        {{-- <option value="payment">Pembayaran</option> --}}
                        <option value="income">Pemasukan</option>
                        <option value="transfer_in">Transfer Masuk</option>
                        {{-- <option value="adjustment_in">Penyesuaian</option>
                        <option value="refund_in">Refund</option> --}}
                    </select>
                    @error('form.transaction_type')
                        <span class="text-red-500 text-xs">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="block mb-1">Tanggal</label>
                    <input type="datetime-local" wire:model.live="form.transaction_date"
                        class="w-full border px-3 py-2 rounded">
                    @error('form.transaction_date')
                        <span class="text-red-500 text-xs">{{ $message }}</span>
                    @enderror
                </div>

                <div x-data="{
                    amountFormatted: '',
                    update(value) {
                        value = value.replace(/\D/g, '');
                        this.amountFormatted = new Intl.NumberFormat('id-ID').format(value);
                        $wire.form.amount = value;
                    }
                }" x-init="update('{{ old('form.amount', $form['amount'] ?? '') }}')">

                    <label class="block mb-1">Jumlah (Rp)</label>
                    <input type="text" x-model="amountFormatted" @input="update($event.target.value)"
                        class="w-full border px-3 py-2 rounded" placeholder="Contoh: 1.000.000">

                    @error('form.amount')
                        <span class="text-red-500 text-xs">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="block mb-1">Metode</label>
                    <select wire:model.live="form.payment_method"
                        class="w-full border border-zinc-300 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 px-3 py-2 rounded">
                        <option value="">-- Pilih --</option>
                        <option value="cash">Tunai</option>
                        <option value="transfer">Transfer</option>
                        <option value="qris">QRIS</option>
                    </select>
                    @error('form.payment_method')
                        <span class="text-red-500 text-xs">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="block mb-1">Catatan</label>
                    <textarea wire:model.live="form.note" rows="3" class="w-full border px-3 py-2 rounded"></textarea>
                </div>
            </div>

            <div class="mt-5 flex justify-end gap-2">
                <button @click="open = false" class="px-4 py-2 bg-gray-300 rounded">Batal</button>
                <button wire:click="save" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                    Simpan
                </button>
            </div>
        </div>
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
                        <span><strong>Tipe Transaksi:</strong></span>
                        <span>{{ ucfirst($transactionDetail['trans_type']) ?? '-' }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span><strong>Catatan:</strong></span>
                        <span>{{ $transactionDetail['note'] ?? '-' }}</span>
                    </div>

                    @if ($transactionDetail['stock_id'])
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
                    @else
                        <div class="flex justify-between">
                            <span><strong>Nominal:</strong></span>
                            <span>Rp
                                {{ number_format($transactionDetail['tagihan'], 0, ',', '.') }}</span>
                        </div>
                    @endif


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
