<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Companie;
use Livewire\WithFileUploads;
use App\Models\CompanieBackground;
use Illuminate\Support\Facades\Storage;

class CompanyBackgroundManager extends Component
{
    use WithFileUploads;

    public $background_image;
    public $company;
    public $backgrounds = [];

    public function mount()
    {
        // Ambil perusahaan pertama
        $this->company = Companie::first();

        // Ambil background yang terkait dengan perusahaan
        $this->backgrounds = $this->company->backgrounds;
    }

    // Fungsi untuk upload background
    public function uploadBackground()
    {
        // Validasi file yang diupload
        $this->validate([
            'background_image' => 'image|max:2048', // Max 2MB
        ]);

        // Generate nama file unik
        $filename = uniqid('background_') . '.' . $this->background_image->getClientOriginalExtension();

        // Simpan file gambar di storage
        Storage::disk('public')->putFileAs('company', $this->background_image, $filename);

        // Simpan data background ke database
        $this->company->backgrounds()->create([
            'image_path' => $filename,
        ]);

        // Reset input setelah berhasil upload
        $this->reset('background_image');

        // Ambil ulang data background yang terbaru
        $this->backgrounds = $this->company->backgrounds()->latest()->get();

        // Dispatch sukses alert
        $this->dispatch('alert-success', ['message' => 'Background berhasil ditambahkan.']);
    }

    // Fungsi untuk menghapus background
    public function deleteBackground($id)
    {
        // Cari background yang ingin dihapus
        $background = CompanieBackground::findOrFail($id);

        // Hapus file gambar dari storage
        Storage::disk('public')->delete('company/' . $background->image_path);

        // Hapus record background dari database
        $background->delete();

        // Ambil ulang data background yang terbaru
        $this->backgrounds = $this->company->backgrounds()->latest()->get();

        // Dispatch sukses alert
        $this->dispatch('alert-success', ['message' => 'Background berhasil dihapus.']);
    }

    public function render()
    {
        return view('livewire.company-background-manager');
    }
}
