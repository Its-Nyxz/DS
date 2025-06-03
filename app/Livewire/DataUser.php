<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class DataUser extends Component
{
    use WithPagination;

    public $search = '';
    public $name, $email, $password, $userId;
    public $isModalOpen = false;

    public function render()
    {
        $users = User::query()
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->paginate(10);

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
        $this->isModalOpen = true;
    }

    public function save()
    {
        $this->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $this->userId,
            'password' => $this->userId ? 'nullable|min:8' : 'required|min:8',
        ]);

        User::updateOrCreate(
            ['id' => $this->userId],
            [
                'name'     => $this->name,
                'email'    => $this->email,
                'password' => $this->password ? Hash::make($this->password) : User::find($this->userId)->password,
            ]
        );

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
}
