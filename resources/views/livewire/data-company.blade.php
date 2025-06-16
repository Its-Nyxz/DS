<x-card title="Profil Perusahaan">
    <div class="flex flex-col md:flex-row items-center justify-center gap-8 mb-8">
        {{-- Logo --}}
        <div class="text-center">
            <label for="logo-upload" class="cursor-pointer group">
                @if ($company->logo)
                    <img src="{{ asset('storage/company/' . $company->logo) }}"
                        class="w-32 h-32 rounded-full object-cover shadow-md group-hover:opacity-80 transition" />
                @else
                    <div
                        class="w-32 h-32 rounded-full bg-gray-200 flex items-center justify-center shadow-md group-hover:opacity-80 transition">
                        <i class="fas fa-user text-5xl text-gray-500"></i>
                    </div>
                @endif
                <p class="mt-2 text-sm text-gray-500 group-hover:text-blue-600">Klik untuk ganti logo</p>
            </label>
            <input type="file" id="logo-upload" wire:model="logo" class="hidden" accept="image/*" />
        </div>
    </div>


    <div class="grid gap-6 mb-6 md:grid-cols-2">
        <div>
            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Nama Perusahaan</label>
            <input type="text" wire:model.live="name"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg 
                focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 
                dark:bg-zinc-800 dark:border-zinc-600 dark:text-white"
                required />
        </div>

        <div>
            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Email</label>
            <input type="email" wire:model.live="email"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg 
                focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 
                dark:bg-zinc-800 dark:border-zinc-600 dark:text-white" />
        </div>

        <div>
            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Telepon</label>
            <input type="text" wire:model.live="phone"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg 
                focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 
                dark:bg-zinc-800 dark:border-zinc-600 dark:text-white" />
        </div>

        <div>
            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">NPWP</label>
            <input type="text" wire:model.live="npwp"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg 
                focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 
                dark:bg-zinc-800 dark:border-zinc-600 dark:text-white" />
        </div>

        <div>
            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Nama Pemilik</label>
            <input type="text" wire:model.live="owner_name"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg 
                focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 
                dark:bg-zinc-800 dark:border-zinc-600 dark:text-white" />
        </div>

        <div>
            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Bank</label>
            <input type="text" wire:model.live="bank_name"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg 
                focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 
                dark:bg-zinc-800 dark:border-zinc-600 dark:text-white" />
        </div>

        <div class="md:col-span-2">
            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Alamat</label>
            <textarea wire:model.live="address"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg 
                focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 
                dark:bg-zinc-800 dark:border-zinc-600 dark:text-white"
                rows="3"></textarea>
        </div>

        <div class="md:col-span-2">
            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">No Rekening</label>
            <input type="text" wire:model.live="bank_account"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg 
                focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 
                dark:bg-zinc-800 dark:border-zinc-600 dark:text-white" />
        </div>

        <div class="md:col-span-2">
            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Slogan</label>
            <input type="text" wire:model.live="slogan"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg 
        focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 
        dark:bg-zinc-800 dark:border-zinc-600 dark:text-white" />
        </div>

        <div class="md:col-span-2">
            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Deskripsi Toko</label>
            <textarea wire:model.live="description" rows="3"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg 
        focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 
        dark:bg-zinc-800 dark:border-zinc-600 dark:text-white"></textarea>
        </div>

        <div class="md:col-span-2">
            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Promo</label>
            <textarea wire:model.live="promo" rows="2"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg 
        focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 
        dark:bg-zinc-800 dark:border-zinc-600 dark:text-white"></textarea>
        </div>

        <div class="md:col-span-2">
            <livewire:company-banner-manager />
        </div>

    </div>



    <button wire:click="save"
        class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none 
        focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center 
        dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
        Simpan
    </button>
</x-card>
