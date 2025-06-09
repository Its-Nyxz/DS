<x-card title="Pengaturan Karyawan">
    <div class="p-4">

        <div class="flex justify-between mb-4">
            <input type="text" wire:model.live="search" class="border rounded p-2 w-1/3" placeholder="Cari user..." />

            <button wire:click="openModal" class="px-4 py-2 bg-blue-600 text-white rounded">
                Tambah User
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full bg-white dark:bg-zinc-800 text-sm">
                <thead class="bg-gray-100 dark:bg-zinc-700 text-gray-700 dark:text-gray-300">
                    <tr>
                        <th class="px-6 py-3">Nama</th>
                        <th class="px-6 py-3">Email</th>
                        <th class="px-6 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr class="border-b dark:border-zinc-700">
                            <td class="px-6 py-4">{{ $user->name }}</td>
                            <td class="px-6 py-4">{{ $user->email }}</td>
                            <td class="px-6 py-4">
                                {{-- Tombol Edit --}}
                                <button wire:click="edit({{ $user->id }})"
                                    class="text-blue-600 hover:text-blue-800 mr-2" title="Edit">
                                    <i class="fas fa-pen"></i>
                                </button>

                                {{-- Tombol Hapus --}}
                                <button
                                    onclick="confirmAlert('Yakin ingin menghapus User ini?', 'Ya, hapus!', () => @this.call('delete', {{ $user->id }}))"
                                    class="text-red-600 hover:text-red-800" title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center py-4">Tidak ada data</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $users->links() }}
        </div>

        {{-- Modal --}}
        <div x-data="{ open: @entangle('isModalOpen') }">
            <div x-show="open" class="fixed inset-0 bg-black/30 backdrop-blur-sm z-40"></div>
            <div x-show="open"
                class="fixed z-50 top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-md bg-white dark:bg-zinc-800 p-6 rounded shadow"
                x-transition>
                <h2 class="text-xl font-semibold mb-4">
                    {{ $userId ? 'Edit User' : 'Tambah User' }}
                </h2>

                <div class="space-y-4">
                    {{-- Nama --}}
                    <div>
                        <label class="block text-sm mb-1">Nama</label>
                        <input type="text" wire:model.live="name"
                            class="w-full border rounded px-3 py-2 dark:bg-zinc-800 dark:text-white">
                        @error('name')
                            <small class="text-red-500">{{ $message }}</small>
                        @enderror
                    </div>

                    {{-- Email --}}
                    <div>
                        <label class="block text-sm mb-1">Email</label>
                        <input type="email" wire:model.live="email"
                            class="w-full border rounded px-3 py-2 dark:bg-zinc-800 dark:text-white">
                        @error('email')
                            <small class="text-red-500">{{ $message }}</small>
                        @enderror
                    </div>

                    {{-- Password --}}
                    <div>
                        <label class="block text-sm mb-1">Password</label>
                        <input type="password" wire:model.live="password"
                            class="w-full border rounded px-3 py-2 dark:bg-zinc-800 dark:text-white">
                        @error('password')
                            <small class="text-red-500">{{ $message }}</small>
                        @enderror
                        @if ($userId)
                            <p class="text-xs text-gray-400">Kosongkan jika tidak ingin mengubah</p>
                        @endif
                    </div>

                    {{-- Pilih Role --}}
                    <div>
                        <label class="block text-sm mb-1">Role</label>
                        <select wire:model.live="role"
                            class="w-full border rounded px-3 py-2 dark:bg-zinc-800 dark:text-white">
                            <option value="">-- Pilih Role --</option>
                            @foreach ($availableRoles as $roleName)
                                <option value="{{ $roleName }}">{{ ucfirst($roleName) }}</option>
                            @endforeach
                        </select>
                        @error('role')
                            <small class="text-red-500">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                {{-- Tombol Aksi --}}
                <div class="mt-4 flex justify-end space-x-2">
                    <button @click="open = false" wire:click="resetForm"
                        class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">
                        Batal
                    </button>
                    <button wire:click="save" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        Simpan
                    </button>
                </div>

                {{-- Reset Password --}}
                @if ($userId)
                    <div class="mt-3 text-right">
                        <button
                            onclick="confirmAlert('Yakin ingin mereset password menjadi default (12345678)?', 'Ya, reset!', () => @this.call('resetPassword', {{ $userId }}))"
                            class="text-sm text-red-600 hover:underline hover:text-red-800">
                            Reset Password ke Default
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-card>
