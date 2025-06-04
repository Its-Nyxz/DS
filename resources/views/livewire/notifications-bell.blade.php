<div class="relative" x-data="{ open: false }">
    {{-- Bell Icon --}}
    <button class="relative focus:outline-none" title="Notifikasi"
        @click="open = !open; if (open) { $wire.loadNotifications() }">
        <i class="fas fa-bell text-gray-600 dark:text-gray-300"></i>

        @if ($unreadCount > 0)
            <span class="absolute top-0 right-0 inline-block w-2 h-2 bg-red-500 rounded-full"></span>
        @endif
    </button>

    {{-- Dropdown --}}
    <div x-show="open" @click.outside="open = false" x-transition
        class="absolute right-0 mt-2 w-72 bg-white dark:bg-zinc-800 shadow-lg rounded z-50">
        <div class="flex justify-between items-center px-4 py-2 border-b dark:border-zinc-700 text-sm font-semibold">
            <span>Notifikasi</span>
            <button wire:click="markAsRead" class="text-xs text-blue-500 hover:underline">
                Tandai dibaca
            </button>
        </div>

        <ul class="max-h-64 overflow-y-auto">
            @forelse ($notifications as $notif)
                <li wire:click="markAsRead('{{ $notif->id }}')"
                    class="px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-zinc-700 cursor-pointer">
                    <div class="font-medium">{{ $notif->data['title'] ?? 'Notifikasi' }}</div>
                    <div class="text-xs text-gray-500">{{ $notif->data['message'] ?? '-' }}</div>
                </li>
            @empty
                <li class="px-4 py-2 text-sm text-gray-500 text-center">
                    Tidak ada notifikasi
                </li>
            @endforelse
        </ul>
    </div>
</div>
