<x-card title="Data Supplier">
    <div class="p-4">
        <div class="flex justify-between mb-4">
            <div class="flex flex-wrap gap-2 w-full md:w-auto">
                <input type="text" wire:model.live="search" placeholder="Cari Pemasok..."
                    class="border px-4 py-2 rounded w-1/3">
                <select wire:model.live="orderBy" class="border px-3 py-2 rounded dark:bg-zinc-800 dark:text-white">
                    <option value="created_at">Terbaru</option>
                    <option value="name">Nama</option>
                    <option value="phone">No Hp</option>
                </select>

                <select wire:model.live="orderDirection"
                    class="border px-3 py-2 rounded dark:bg-zinc-800 dark:text-white">
                    <option value="asc">Naik</option>
                    <option value="desc">Turun</option>
                </select>
            </div>
            <div class="flex space-x-2">
                <button wire:click="openModal" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    Tambah Supplier
                </button>
                <livewire:imports-supplier />
            </div>
        </div>
        <div class="bg-white dark:bg-zinc-900 rounded shadow">
            <table class="w-full text-sm table-auto">
                <thead class="bg-gray-100 dark:bg-zinc-800">
                    <tr>
                        <th class="p-3 text-left">Nama</th>
                        <th class="p-3 text-left">Telepon</th>
                        <th class="p-3 text-left">Alamat</th>
                        <th class="p-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($suppliers as $supplier)
                        <tr class="border-b dark:border-zinc-700">
                            <td class="p-3">{{ $supplier->name }}</td>
                            <td class="p-3">{{ $supplier->phone }}</td>
                            <td class="p-3">{{ $supplier->address }}</td>
                            <td class="p-3 text-center space-x-2">
                                <!-- Tombol Edit -->
                                <button wire:click="edit({{ $supplier->id }})"
                                    class="px-2 py-1 text-sm text-yellow-600 hover:text-white hover:bg-yellow-600 rounded transition duration-150"
                                    title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>

                                <!-- Tombol Hapus -->
                                <button type="button"
                                    onclick="confirmAlert('Yakin ingin menghapus data ini?', 'Ya, hapus!', () => @this.call('delete', {{ $supplier->id }}))"
                                    class="px-2 py-1 text-sm text-red-600 hover:text-white hover:bg-red-600 rounded transition duration-150"
                                    title="Hapus">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="p-3 text-center text-gray-500">Tidak ada data supplier.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $suppliers->links() }}
        </div>

        {{-- Modal --}}
        @if ($isModalOpen)
            <div class="fixed inset-0 bg-black/30 backdrop-blur-sm flex justify-center items-center z-50">
                <div class="bg-white dark:bg-zinc-900 p-6 rounded shadow w-96">
                    <h2 class="text-lg font-semibold mb-4">{{ $supplierId ? 'Edit' : 'Tambah' }} Supplier</h2>

                    <input type="text" wire:model.defer="name" class="w-full border rounded px-3 py-2 mb-2"
                        placeholder="Nama Supplier">
                    @error('name')
                        <div class="text-red-500 text-sm">{{ $message }}</div>
                    @enderror

                    <input type="text" wire:model.defer="phone" class="w-full border rounded px-3 py-2 mb-2"
                        placeholder="Telepon">
                    <textarea wire:model.defer="address" class="w-full border rounded px-3 py-2 mb-4" placeholder="Alamat"></textarea>

                    <div class="flex justify-end space-x-2">
                        <button wire:click="resetForm" class="text-gray-600">Batal</button>
                        <button wire:click="save" class="bg-blue-600 text-white px-4 py-2 rounded">Simpan</button>
                    </div>
                </div>
            </div>
        @endif
    </div>

</x-card>
