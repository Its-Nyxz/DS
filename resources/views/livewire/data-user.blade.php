<x-card title="Pengaturan Karyawan">
    <div class="p-4">

        <div class="flex justify-between mb-4">
            <input type="text" wire:model.live="search" class="border rounded p-2 w-1/3" placeholder="Cari user..." />

            <button wire:click="openModal" class="px-4 py-2 bg-blue-600 text-white rounded">
                Tambah User
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500">
                <thead class="bg-gray-100 text-gray-700 uppercase text-xs">
                    <tr>
                        <th class="px-6 py-3">Nama</th>
                        <th class="px-6 py-3">Email</th>
                        <th class="px-6 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr class="bg-white border-b">
                            <td class="px-6 py-4">{{ $user->name }}</td>
                            <td class="px-6 py-4">{{ $user->email }}</td>
                            <td class="px-6 py-4">
                                <button wire:click="edit({{ $user->id }})" class="text-blue-600 hover:underline">
                                    Edit
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
        @if ($isModalOpen)
            <div class="fixed inset-0 z-50 flex items-center justify-center backdrop-blur-sm bg-black/30">
                <div class="bg-white dark:bg-gray-800 p-6 rounded w-full max-w-md shadow-lg">
                    <h2 class="text-lg font-semibold mb-4">
                        {{ $userId ? 'Edit User' : 'Tambah User' }}
                    </h2>

                    <div class="space-y-4">
                        <div>
                            <label class="text-sm">Nama</label>
                            <input type="text" wire:model.live="name" class="w-full rounded border p-2" />
                            @error('name')
                                <span class="text-red-500 text-xs">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label class="text-sm">Email</label>
                            <input type="email" wire:model.live="email" class="w-full rounded border p-2" />
                            @error('email')
                                <span class="text-red-500 text-xs">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label class="text-sm">Password</label>
                            <input type="password" wire:model.live="password" class="w-full rounded border p-2" />
                            @error('password')
                                <span class="text-red-500 text-xs">{{ $message }}</span>
                            @enderror
                            @if ($userId)
                                <p class="text-xs text-gray-400">Kosongkan jika tidak ingin mengubah</p>
                            @endif
                        </div>
                    </div>

                    <div class="mt-4 flex justify-end space-x-2">
                        <button wire:click="resetForm" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">
                            Batal
                        </button>
                        <button wire:click="save" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                            Simpan
                        </button>
                    </div>

                    @if ($userId)
                        <div class="mt-2 text-right">
                            <button
                                onclick="confirmAlert('Yakin ingin mereset password menjadi default (12345678)?', 'Ya, hapus!', () => @this.call('resetPassword', {{ $userId }}))"
                                class="text-sm text-red-600 hover:underline hover:text-red-800">
                                Reset Password ke Default
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        @endif


    </div>


</x-card>
