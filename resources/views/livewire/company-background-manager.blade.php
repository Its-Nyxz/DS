<div class="space-y-4">
    <!-- Tampilkan Background yang sudah ada -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach ($backgrounds as $background)
            <div class="relative group">
                <img src="{{ asset('storage/company/' . $background->image_path) }}" alt="Background" class="w-full h-48 object-cover rounded-lg">
                <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 bg-black bg-opacity-50 transition-opacity duration-300">
                    <button wire:click="deleteBackground({{ $background->id }})" class="text-white bg-red-600 hover:bg-red-700 px-4 py-2 rounded-md">
                        Hapus
                    </button>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Form untuk upload background -->
    <form wire:submit.prevent="uploadBackground" class="space-y-4">
        <div>
            <label for="background_image" class="block text-sm font-medium text-gray-700">Upload Background</label>
            <input type="file" wire:model="background_image" id="background_image" class="mt-2 block w-full">
            @error('background_image') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <div>
            <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md">
                Upload Background
            </button>
        </div>
    </form>
</div>
