<div class="relative" x-data="{ open: false }">
    <button @click="open = !open; if (open) $wire.loadNotifications()" class="relative">
        <i class="fa-solid fa-hand-holding-dollar text-gray-600 dark:text-gray-300"></i>

        @if (count($hutangPiutang))
            <span class="absolute top-0 right-0 inline-block w-2 h-2 bg-red-500 rounded-full"></span>
        @endif
    </button>

    <div x-show="open" @click.outside="open = false"
        class="absolute right-0 mt-2 w-80 bg-white dark:bg-zinc-800 rounded shadow-lg z-50">
        <div class="px-4 py-2 font-semibold border-b dark:border-zinc-700">Hutang / Piutang Belum Lunas</div>

        <ul class="max-h-64 overflow-y-auto text-sm">
            @forelse ($hutangPiutang as $item)
                @php
                    $bgColor = match (strtolower($item['type'])) {
                        'utang' => 'bg-red-100 dark:bg-red-900',
                        'piutang' => 'bg-green-100 dark:bg-green-900',
                        default => '',
                    };
                @endphp

                <li class="px-4 py-2 hover:bg-gray-100 dark:hover:bg-zinc-700 cursor-pointer {{ $bgColor }}"
                    @click="window.location.href = '{{ $item['url'] }}'">
                    <div class="font-medium text-gray-800 dark:text-white">
                        {{ $item['title'] }}
                    </div>
                    <div class="text-gray-600 dark:text-gray-300">
                        {{ $item['message'] }}
                    </div>
                </li>
            @empty
                <li class="px-4 py-2 text-center text-gray-400">Semua lunas 🎉</li>
            @endforelse
        </ul>
    </div>
</div>
