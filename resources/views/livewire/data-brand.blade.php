<x-card title="Data Merek">
    <div class="p-6">
        {{-- Header --}}
        <div class="flex justify-between mb-4">
            <div class="flex flex-wrap gap-2 w-full md:w-auto">
                <input type="text" wire:model.live="search" placeholder="Cari merk..."
                    class="border px-4 py-2 rounded w-1/3">
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
            <div class="flex space-x-2">
                <button wire:click="openModal" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    Tambah Merk
                </button>
                <livewire:imports-brand />
            </div>
        </div>

        {{-- Card Grid --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($brands as $brand)
                <div class="bg-white dark:bg-zinc-800 shadow rounded p-4 flex flex-col justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800 dark:text-white">{{ $brand->name }}</h3>
                    </div>
                    <div class="flex items-center space-x-2 mt-4">
                        <!-- Edit Button -->
                        <button wire:click="edit({{ $brand->id }})"
                            class="px-2 py-1 text-sm text-yellow-600 hover:text-white hover:bg-yellow-600 rounded transition duration-150"
                            title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>

                        <!-- Delete Button -->
                        <button type="button"
                            onclick="confirmAlert('Yakin ingin menghapus data ini?', 'Ya, hapus!', () => @this.call('delete', {{ $brand->id }}))"
                            class="px-2 py-1 text-sm text-red-600 hover:text-white hover:bg-red-600 rounded transition duration-150"
                            title="Hapus">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                </div>
            @empty
                <div class="col-span-full text-center text-gray-500">Tidak ada data merek.</div>
            @endforelse
        </div>

        {{-- Pagination --}}
        <div class="mt-6">
            {{ $brands->links() }}
        </div>

        {{-- Modal --}}
        <div x-data="{ open: @entangle('isModalOpen') }">
            <div x-show="open" class="fixed inset-0 bg-black/20 backdrop-blur-sm z-40"></div>
            <div x-show="open"
                class="fixed z-50 top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-md bg-white dark:bg-zinc-800 p-6 rounded shadow"
                x-transition>
                <h2 class="text-xl font-semibold mb-4">{{ $brandId ? 'Edit' : 'Tambah' }} Merek</h2>

                <div class="mb-4">
                    <label class="block mb-1">Nama</label>
                    <input type="text" wire:model.defer="name" class="w-full border rounded px-3 py-2"
                        placeholder="Nama merek">
                    @error('name')
                        <small class="text-red-500">{{ $message }}</small>
                    @enderror
                </div>

                <div class="flex justify-end space-x-2">
                    <button @click="open = false" class="bg-gray-300 px-4 py-2 rounded hover:bg-gray-400">Batal</button>
                    <button wire:click="save" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        Simpan
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-card>
