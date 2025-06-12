<div x-data="{
    open: false,
    value: @entangle('value'),
    suggestions: @entangle('suggestions'),
    allSuggestions: @entangle('allSuggestions'), // Semua data yang sudah diambil
    filteredSuggestions: [],
    filterSuggestions() {
        this.filteredSuggestions = this.allSuggestions.filter(suggestion =>
            suggestion.toLowerCase().includes(this.value.toLowerCase())
        );
    }
}">
    <!-- Input dengan Focus dan Input Event -->
    <input type="text" x-model="value" @focus="open = true; filterSuggestions()" @input="filterSuggestions()"
        @click.away="open = false" class="w-full border rounded px-3 py-2 dark:bg-zinc-800 dark:text-white"
        placeholder="Cari..." autocomplete="off" />

    <!-- Daftar Saran yang difilter -->
    <ul x-show="open && filteredSuggestions.length > 0" x-transition
        class="absolute z-20 w-full bg-white border border-gray-300 rounded mt-1 max-h-60 overflow-auto">
        <template x-for="(suggestion, index) in filteredSuggestions" :key="index">
            <li @click="value = suggestion; open = false; $wire.selectSuggestion(suggestion)"
                class="px-4 py-2 bg-white dark:bg-zinc-800 text-gray-800 dark:text-white hover:bg-gray-100 dark:hover:bg-zinc-700 hover:text-black dark:hover:text-gray-200 cursor-pointer transition duration-150">
                <span x-text="suggestion"></span>
            </li>
        </template>
    </ul>
</div>
