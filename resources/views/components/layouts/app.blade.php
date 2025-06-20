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

            document.addEventListener('alpine:init', () => {
                Alpine.data('rupiahInput', (model) => ({
                    rawValue: '',

                    init() {
                        this.$watch('rawValue', (val) => {
                            const clean = val.replace(/\D/g, '');
                            this.rawValue = new Intl.NumberFormat('id-ID').format(clean);
                            this.$dispatch('input', {
                                detail: clean
                            }); // trigger Livewire
                            this.$el.dispatchEvent(new CustomEvent('input', {
                                bubbles: true
                            }));
                        });
                    }
                }));
            });
        </script>
    @endpush
    @stack('scripts')
</x-layouts.app.sidebar>
