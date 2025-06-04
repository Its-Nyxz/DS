<?php

namespace App\Livewire;

use Livewire\Component;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DataRolePermissions extends Component
{
    public $roles;
    public $permissions;
    public $selectedPermissions = [];

    public function mount()
    {
        $this->roles = Role::all();
        $this->permissions = Permission::all();

        foreach ($this->roles as $role) {
            $this->selectedPermissions[$role->id] = $role->permissions->pluck('name')->toArray();
        }
    }

    public function updated($propertyName)
    {
        if (str_starts_with($propertyName, 'selectedPermissions')) {
            $roleId = explode('.', $propertyName)[1];
            $role = Role::find($roleId);
            $permissions = $this->selectedPermissions[$roleId] ?? [];
            $role->syncPermissions($permissions);

            $this->dispatch('alert-success', ['message' => "Permission untuk role '{$role->name}' diperbarui."]);

            // Atau arahkan ke route tertentu
            return $this->redirect(route('permissions.index'));
        }
    }

    public function render()
    {
        return view('livewire.data-role-permissions');
    }
}
