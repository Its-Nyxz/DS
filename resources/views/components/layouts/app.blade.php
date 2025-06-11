<x-layouts.app.sidebar :title="$title ?? null">
    <flux:main>
        {{ $slot }}
    </flux:main>

    @push('scripts')
        <script>
            window.addEventListener('alert-success', event => {
                const {
                    message,
                    title
                } = event.detail[0];
                successAlert(message, title);
            });

            window.addEventListener('alert-error', event => {
                const {
                    message,
                    title
                } = event.detail[0];
                errorAlert(message, title);
            });

            window.addEventListener('alert-warning', event => {
                const {
                    message,
                    title
                } = event.detail[0];
                warningAlert(message, title);
            });
        </script>
    @endpush
    @stack('scripts')
</x-layouts.app.sidebar>
