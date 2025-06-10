<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-white dark:bg-zinc-800">
<flux:sidebar sticky stashable class="w-64 border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

        <a href="{{ route('dashboard') }}" class="me-5 flex items-center space-x-2 rtl:space-x-reverse" wire:navigate>
            <x-app-logo />
        </a>

        <flux:navlist variant="outline">
            <flux:navlist.group :heading="__('Platform')" class="grid">
                <flux:navlist.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')"
                    wire:navigate>{{ __('Dashboard') }}</flux:navlist.item>
            </flux:navlist.group>
            @can('view-data')
            <flux:navlist.group :heading="__('Master Data')" icon="folder" expandable
                :expanded="request()->routeIs('units.*') || request()->routeIs('brands.*') || request()->routeIs(
                    'suppliers.*') || request()->routeIs('items.*')">
                @can('manage-unit')             
                <flux:navlist.item icon="table-cells" :href="route('units.index')" :current="request()->routeIs('units.*')"
                    wire:navigate>
                    {{ __('Satuan') }}
                </flux:navlist.item>
                @endcan

                @can('manage-brand')    
                <flux:navlist.item icon="tag" :href="route('brands.index')"
                    :current="request()->routeIs('brands.*')" wire:navigate>
                    {{ __('Merek') }}
                </flux:navlist.item>
                @endcan
                
                @can('manage-supplier')  
                <flux:navlist.item icon="truck" :href="route('suppliers.index')"
                    :current="request()-> routeIs('suppliers.*')" wire:navigate>
                    {{ __('Supplier') }}
                </flux:navlist.item>
                @endcan

                @can('manage-item')                   
                <flux:navlist.item icon="cube" :href="route('items.index')" :current="request()->routeIs('items.*')"
                    wire:navigate>
                    {{ __('Barang') }}
                </flux:navlist.item>
                @endcan
            </flux:navlist.group>
            @endcan

            @can('manage-supplier-item')  
                <flux:navlist.item icon="link" :href="route('itemsuppliers.index')"
                    :current="request()-> routeIs('itemsuppliers.*')" wire:navigate>
                    {{ __('Supplier Barang') }}
                </flux:navlist.item>
            @endcan

            <flux:navlist.group :heading="__('Transaksi Stok')" icon="folder" expandable
                :expanded="request()->routeIs('transactions.index')">
                 <flux:navlist.item icon="arrow-left" :href="route('transactions.index', ['type' => 'in'])"
                    :current="request()->is('transactions/in')" wire:navigate>
                    {{ __('Masuk') }}
                </flux:navlist.item>

                <flux:navlist.item icon="arrow-right" :href="route('transactions.index', ['type' => 'out'])"
                    :current="request()->is('transactions/out')" wire:navigate>
                    {{ __('Keluar') }}
                </flux:navlist.item>

                @can('view-retur')    
                    <flux:navlist.item icon="arrows-right-left" :href="route('transactions.index', ['type' => 'retur'])"
                        :current="request()->is('transactions/retur')" wire:navigate>
                        {{ __('Retur') }}
                    </flux:navlist.item>
                @endcan

                @can('view-opname')    
                    <flux:navlist.item icon="book-open" :href="route('transactions.index', ['type' => 'opname'])"
                        :current="request()->is('transactions/opname')" wire:navigate>
                        {{ __('Opname') }}
                    </flux:navlist.item>
                @endcan
            </flux:navlist.group>

            @can('view-laporan') 
            {{-- <flux:navlist.group :heading="__('Laporan')" icon="chart-bar" expandable
                :expanded="request()->routeIs('reports.index') || request()->routeIs('reportstock.*')">
                <flux:navlist.item icon="arrow-down-tray" :href="route('reports.index', ['type' => 'in'])"
                    :current="request()->is('reports/in')" wire:navigate>
                    {{ __('Barang Masuk') }}
                </flux:navlist.item>

                <flux:navlist.item icon="arrow-up-tray" :href="route('reports.index', ['type' => 'out'])"
                    :current="request()->is('reports/out')" wire:navigate>
                    {{ __('Barang Keluar') }}
                </flux:navlist.item>

                <flux:navlist.item icon="arrow-uturn-left" :href="route('reports.index', ['type' => 'retur'])"
                    :current="request()->is('reports/retur')" wire:navigate>
                    {{ __('Retur') }}
                </flux:navlist.item>
            </flux:navlist.group> --}}
            <flux:navlist.item icon="chart-bar" :href="route('reportstock.index')" :current="request()->routeIs('reportstock.*')"
                wire:navigate>
                {{ __('Laporan Stok') }}
            </flux:navlist.item>
            @endcan

            @can('view-setting')    
            <flux:navlist.group :heading="__('Pengaturan')" icon="wrench" expandable
                :expanded="request()->routeIs('users.*') || request()->routeIs('companie.*')|| request()->routeIs('permissions.*')">
                @can('manage-users')                   
                <flux:navlist.item icon="users" :href="route('users.index')" :current="request()->routeIs('users.*')"
                    wire:navigate>
                    {{ __('Karyawan') }}
                </flux:navlist.item>
                @endcan

                @can('manage-companie')               
                <flux:navlist.item icon="building-storefront" :href="route('companie.index')"
                    :current="request()->routeIs('companie.*')" wire:navigate>
                    {{ __('Tentang') }}
                </flux:navlist.item>
                @endcan

                @can('manage-permissions')
                <flux:navlist.item icon="adjustments-horizontal" :href="route('permissions.index')"
                    :current="request()->routeIs('permissions.*')" wire:navigate>
                    {{ __('Perizinan Karyawan') }}
                </flux:navlist.item>
                @endcan
            </flux:navlist.group>
            @endcan

        </flux:navlist>

        <flux:spacer />

        {{-- <flux:navlist variant="outline">
            <flux:navlist.item icon="folder-git-2" href="https://github.com/laravel/livewire-starter-kit"
                target="_blank">
                {{ __('Repository') }}
            </flux:navlist.item>

            <flux:navlist.item icon="book-open-text" href="https://laravel.com/docs/starter-kits#livewire"
                target="_blank">
                {{ __('Documentation') }}
            </flux:navlist.item>
        </flux:navlist> --}}

        <!-- Desktop User Menu -->
        <flux:dropdown position="bottom" align="start">
            <flux:profile :name="auth()->user()->name" :initials="auth()->user()-> initials()"
                icon-trailing="chevrons-up-down" />

            <flux:menu class="w-[220px]">
                <flux:menu.radio.group>
                    <div class="p-0 text-sm font-normal">
                        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                            <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                <span
                                    class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                    {{ auth()->user()->initials() }}
                                </span>
                            </span>

                            <div class="grid flex-1 text-start text-sm leading-tight">
                                <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                            </div>
                        </div>
                    </div>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <flux:menu.radio.group>
                    <flux:menu.item :href="route('settings.profile')" icon="cog" wire:navigate>
                        {{ __('Settings') }}</flux:menu.item>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                        {{ __('Log Out') }}
                    </flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    </flux:sidebar>

    <flux:header class="hidden lg:flex justify-end items-center border-b bg-white dark:bg-zinc-800 dark:border-zinc-700 px-6 py-4">
        <div class="flex items-center space-x-4">
            {{-- Notifikasi Stok Rendah --}}
            <livewire:stock-notification />

            {{-- Notifikasi Bell --}}
            <livewire:notifications-bell />
        </div>
    </flux:header>

    <!-- Mobile Header with Notification Bell -->
    <flux:header class="lg:hidden flex items-center justify-between px-4 py-2 border-b bg-white dark:bg-zinc-800 dark:border-zinc-700">

        {{-- Sidebar Toggle --}}
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
        
        {{-- Notifikasi Bell --}}
        <div class="flex items-center space-x-2">
            <livewire:stock-notification />
            
            <livewire:notifications-bell />

            {{-- Dropdown User --}}
            <flux:dropdown position="top" align="end">
                <flux:profile :initials="auth()->user()->initials()" icon-trailing="chevron-down" />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('settings.profile')" icon="cog" wire:navigate>
                            {{ __('Settings') }}</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </div>
    </flux:header>

    {{ $slot }}

    @fluxScripts
</body>

</html>
