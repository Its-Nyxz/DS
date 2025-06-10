<div>
    <!-- Tombol untuk membuka modal Impor -->
    <button wire:click="$set('isModalOpen', true)" class="bg-yellow-600 text-white px-4 py-2 rounded hover:bg-yellow-700">
        Impor Data
    </button>

    <!-- Modal Impor -->
    <div x-data="{ open: @entangle('isModalOpen') }">
        <div x-show="open" class="fixed inset-0 bg-black/20 backdrop-blur-sm z-40"></div>
        <div x-show="open"
            class="fixed z-50 top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-md bg-white dark:bg-zinc-800 p-6 rounded shadow"
            x-transition>
            <h2 class="text-xl font-semibold mb-4">Impor Data Pemasok</h2>

            <form wire:submit.prevent="import">
                <div class="mb-4">
                    <label class="block mb-1">Pilih File Excel</label>
                    <input type="file" wire:model.live="file" class="w-full border rounded px-3 py-2">
                    @error('file')
                        <small class="text-red-500">{{ $message }}</small>
                    @enderror
                </div>

                <div class="mb-4">
                    <!-- Tombol untuk Mengunduh Template -->
                    <a href="{{ route('suppliers.template') }}"
                        class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 w-full text-center block">
                        Unduh Template Excel
                    </a>
                </div>

                <div class="flex justify-end space-x-2">
                    <button type="button" @click="open = false"
                        class="bg-gray-300 px-4 py-2 rounded hover:bg-gray-400">Batal</button>
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        Impor
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
