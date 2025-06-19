<x-layouts.app>
    @if ($type === 'arus')
        <livewire:data-cash-flow />
    @else
        <livewire:data-cash-transaction :type="$type" />
    @endif
</x-layouts.app>
