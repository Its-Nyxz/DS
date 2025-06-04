<x-card title="Pengaturan Role & Perizinan">
    <div class="p-4 overflow-x-auto">
        <table class="min-w-full text-sm text-left">
            <thead class="text-xs text-gray-700 bg-gray-100">
                <tr>
                    <th class="p-2">Permission</th>
                    @foreach ($roles as $role)
                        <th class="p-2 text-center">{{ ucfirst($role->name) }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($permissions as $permission)
                    <tr class="border-b">
                        <td class="p-2">{{ $permission->name }}</td>
                        @foreach ($roles as $role)
                            <td class="p-2 text-center">
                                <input type="checkbox" wire:model.live="selectedPermissions.{{ $role->id }}"
                                    value="{{ $permission->name }}">
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>

    </div>
</x-card>
