<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Companie;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

class DataCompany extends Component
{
    use WithFileUploads;
    public $company;
    public $logo;
    public $logo_preview;
    public $name;
    public $email;
    public $phone;
    public $address;
    public $npwp;
    public $owner_name;
    public $bank_name;
    public $bank_account;

    public function mount()
    {
        $this->company = Companie::firstOrNew();

        // Set nilai awal ke properti
        $this->logo         = $this->company->logo;
        $this->name         = $this->company->name;
        $this->email        = $this->company->email;
        $this->phone        = $this->company->phone;
        $this->address      = $this->company->address;
        $this->npwp         = $this->company->npwp;
        $this->owner_name   = $this->company->owner_name;
        $this->bank_name    = $this->company->bank_name;
        $this->bank_account = $this->company->bank_account;
    }
    public function updatedLogo()
    {
        $this->validate([
            'logo' => 'image|max:1024',
        ]);

        $filename = 'company-logo.' . $this->logo->getClientOriginalExtension();

        // Pastikan penyimpanan manual
        Storage::disk('public')->putFileAs('company', $this->logo, $filename);

        // hanya simpan nama file (bukan path lengkap)
        $this->company->update([
            'logo' => $filename,
        ]);

        $this->dispatch('alert-success', ['message' => 'Logo berhasil diunggah.']);
    }

    public function save()
    {
        $this->validate([
            'name'         => 'required|string|max:255',
            'email'        => 'nullable|email',
            'phone'        => 'nullable|string',
            'address'      => 'nullable|string',
            'npwp'         => 'nullable|string',
            'owner_name'   => 'nullable|string',
            'bank_name'    => 'nullable|string',
            'bank_account' => 'nullable|string',
        ]);

        $this->company->fill([
            'icon' => $this->icon,
            'name'         => $this->name,
            'email'        => $this->email,
            'phone'        => $this->phone,
            'address'      => $this->address,
            'npwp'         => $this->npwp,
            'owner_name'   => $this->owner_name,
            'bank_name'    => $this->bank_name,
            'bank_account' => $this->bank_account,
        ])->save();

        $this->dispatch('alert-success', ['message' => 'Profil perusahaan disimpan.']);
    }

    public function render()
    {
        return view('livewire.data-company');
    }
}
