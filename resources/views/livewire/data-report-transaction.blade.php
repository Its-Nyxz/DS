@php
    $labels = [
        'in' => 'Masuk',
        'out' => 'Keluar',
        'retur' => 'Retur',
    ];
@endphp

<x-card title="Laporan Barang {{ $labels[$type] ?? ucfirst($type) }}">
    {{-- Search --}}
    <div class="mb-4 flex justify-end">
        <input type="text" wire:model.live="search" placeholder="Cari nama barang..."
            class="border border-gray-300 dark:border-zinc-600 rounded px-3 py-2 text-sm w-1/3 dark:bg-zinc-800 dark:text-gray-200" />
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full bg-white dark:bg-zinc-800 text-sm">
            <thead class="bg-gray-100 dark:bg-zinc-700 text-gray-700 dark:text-gray-300">
                <tr>
                    <th class="px-4 py-3 border">Tanggal</th>
                    <th class="px-4 py-3 border">Barang</th>
                    <th class="px-4 py-3 border">Jumlah</th>
                    <th class="px-4 py-3 border">Catatan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($transactions as $tx)
                    <tr class="border-b dark:border-zinc-700">
                        <td class="px-4 py-2">{{ $tx->created_at->format('d/m/Y') }}</td>
                        <td class="px-4 py-2">{{ $tx->item->name ?? '-' }}</td>
                        <td class="px-4 py-2">{{ $tx->quantity }}</td>
                        <td class="px-4 py-2">{{ $tx->note ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center py-4 text-gray-500">Tidak ada Transaksi.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-4">
            {{ $transactions->links() }}
        </div>
    </div>
</x-card>
