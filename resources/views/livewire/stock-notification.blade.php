<div class="relative" x-data="{ open: false }">
    {{-- box Icon --}}
    <button class="relative focus:outline-none" title="Notifikasi"
        @click="open = !open; if (open) { $wire.loadLowStockItems() }">
        <i class="fa-solid fa-dice-d6 text-gray-600 dark:text-gray-300"></i>
        @if ($unreadCount > 0)
            <span class="absolute top-0 right-0 inline-block w-2 h-2 bg-red-500 rounded-full"></span>
        @endif
    </button>


    {{-- Dropdown --}}
    <div x-show="open" @click.outside="open = false" x-transition
        class="absolute right-0 mt-2 w-72 bg-white dark:bg-zinc-800 shadow-lg rounded z-50">
        <div class="flex justify-between items-center px-4 py-2 border-b dark:border-zinc-700 text-sm font-semibold">
            <span>Notifikasi Stok Rendah</span>
            <button wire:click="markAsRead" class="text-sm text-blue-500 hover:underline">
                Tandai Dibaca
            </button>
        </div>

        <ul class="max-h-64 overflow-y-auto">
            @forelse ($lowStockItems as $item)
                <li @click.prevent="window.location.href = '{{ route('items.index', $item->id) }}'"
                    class="px-4 py-2 text-md hover:bg-gray-100 dark:hover:bg-zinc-700 cursor-pointer">
                    <div class="font-medium">{{ $item->sku }} {{ $item->name }} {{ $item->brand->name }}</div>
                    <div class="text-sm text-gray-300">Stok rendah, silakan tambah stok!</div>
                </li>
            @empty
                <li class="px-4 py-2 text-sm text-gray-300 text-center">
                    Tidak ada notifikasi stok rendah
                </li>
            @endforelse
        </ul>
    </div>
</div>
