<x-layouts.app.sidebar :title="$title ?? null">
    <flux:main>
        {{ $slot }}
    </flux:main>

    @push('scripts')
        <script>
            window.addEventListener('alert-success', event => {
                successAlert(event.detail.message);
            });
            window.addEventListener('alert-error', event => {
                errorAlert(event.detail.message);
            });
        </script>
    @endpush
    @stack('scripts')
</x-layouts.app.sidebar>
