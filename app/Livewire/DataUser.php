<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Component;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class DataUser extends Component
{
    use WithPagination;

    public $search = '';
    public $name, $email, $password, $userId;
    public $role;
    public $availableRoles = [];
    public $isModalOpen = false;

    public function render()
    {
        $currentUser = auth()->user();

        $users = User::where('id', '!=', $currentUser->id) // jangan tampilkan user sendiri
            ->whereDoesntHave('roles', function ($query) use ($currentUser) {
                // Jangan tampilkan user dengan role 'pemilik'
                $query->where('name', 'pemilik');

                // Jika yang login admin, jangan tampilkan user dengan role 'admin' juga
                if ($currentUser->hasRole('admin')) {
                    $query->orWhere('name', 'admin');
                }
            })
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->with(['roles' => fn($q) => $q->where('name', '!=', 'pemilik')]) // exclude role pemilik dari tampilan
            ->orderBy('name')
            ->paginate(10);
        $this->availableRoles = Role::where('name', '!=', 'pemilik')->pluck('name');
        return view('livewire.data-user', compact('users'));
    }

    public function openModal()
    {
        $this->resetForm();
        $this->isModalOpen = true;
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);
        $this->userId  = $user->id;
        $this->name    = $user->name;
        $this->email   = $user->email;
        $this->role = $user->roles()->pluck('name')->first(); // Ambil role pertama
        $this->isModalOpen = true;
    }

    public function save()
    {
        $this->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $this->userId,
            'password' => $this->userId ? 'nullable|min:8' : 'required|min:8',
            'role'     => 'required|exists:roles,name',
        ]);

        $user = User::updateOrCreate(
            ['id' => $this->userId],
            [
                'name'     => $this->name,
                'slug'     => Str::slug($this->name), // ← tambahkan slug
                'email'    => $this->email,
                'password' => $this->password ? Hash::make($this->password) : User::find($this->userId)->password,
            ]
        );

        // Sync role
        if ($this->role) {
            $user->syncRoles([$this->role]);
        }

        $this->resetForm();
        $this->dispatch('alert-success', ['message' => 'User berhasil disimpan']);
    }

    public function resetForm()
    {
        $this->reset(['userId', 'name', 'email', 'password', 'isModalOpen']);
    }

    public function resetPassword($data)
    {
        $user = User::find($data);
        if ($user) {
            $user->password = bcrypt('12345678');
            $user->save();

            $this->dispatch('alert-success', ['message' => 'Password berhasil direset ke 12345678.']);
        }
    }

    public function delete($id)
    {
        $user = User::findOrFail($id);

        // Jangan hapus user dengan role 'pemilik' (opsional, sebagai proteksi tambahan)
        if ($user->hasRole('pemilik')) {
            $this->dispatch('alert-error', ['message' => 'User dengan role pemilik tidak boleh dihapus.']);
            return;
        }

        $user->delete();
        $this->dispatch('alert-success', ['message' => 'User berhasil dihapus.']);
    }
}
