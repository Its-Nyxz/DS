<div>
    <div class="mb-6">
        <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">Tambah Banner Baru</label>
        <input type="file" wire:model.live="banner_image" accept="image/*" class="mb-2" />
        <input type="text" wire:model.live="title" placeholder="Judul (opsional)"
            class="w-full mb-2 px-3 py-2 border rounded dark:bg-zinc-800 dark:border-zinc-600 dark:text-white" />
        <button wire:click="uploadBanner"
            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Unggah</button>

        <div wire:loading wire:target="banner_image" class="mt-2 text-sm text-gray-500">Uploading...</div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        @foreach ($banners as $banner)
            <div class="relative group">
                <img src="{{ asset('storage/company/' . $banner->image_path) }}" alt="{{ $banner->title }}"
                    class="rounded shadow w-full h-32 object-cover" />

                <button wire:click="deleteBanner({{ $banner->id }})"
                    class="absolute top-2 right-2 bg-red-600 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition">
                    Hapus
                </button>
            </div>
        @endforeach
    </div>
</div>
