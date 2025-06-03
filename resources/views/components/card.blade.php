<div
    {{ $attributes->merge(['class' => 'w-full bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-700 rounded-lg shadow overflow-hidden']) }}>
    @if ($title || $subtitle)
        <div class="px-4 py-3 border-b border-gray-200 dark:border-zinc-700 bg-gray-50 dark:bg-zinc-800">
            @if ($title)
                <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
                    {!! $title !!}
                </h2>
            @endif

            @if ($subtitle)
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {!! $subtitle !!}
                </p>
            @endif
        </div>
    @endif

    <div class="p-4">
        {{ $slot }}
    </div>
</div>
