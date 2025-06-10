<?php

namespace App\Livewire;

use Livewire\Component;
use App\Imports\UnitsImport;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;

class ImportsUnit extends Component
{
    use WithFileUploads;

    public $file;
    public $isModalOpen = false;

    // Validasi file yang diupload
    protected $rules = [
        'file' => 'required|mimes:xlsx,xls,csv|max:2048', // Max size 2MB
    ];

    public function render()
    {
        return view('livewire.imports-unit');
    }

    public function openModal()
    {
        $this->isModalOpen = true;
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
    }

    public function import()
    {
        $this->validate();  // Validasi file

        $fileName = $this->file->getClientOriginalName();  // Mendapatkan nama asli file yang di-upload
        if (strpos(strtolower($fileName), 'satuan') === false) {
            $this->dispatch('alert-error', ['message' => 'Nama file harus mengandung kata "satuan".']);
            return;
        }

        try {
            // Import data dari file Excel
            Excel::import(new UnitsImport, $this->file);

            // Pesan sukses setelah impor selesai
            $this->dispatch('alert-success', ['message' => 'Data satuan berhasil diimpor.']);
            $this->dispatch('refreshDataUnit');
        } catch (\Exception $e) {
            // Tangani error jika terjadi kesalahan saat impor
            $this->dispatch('alert-error', ['message' => 'Terjadi kesalahan saat mengimpor data.']);
        }

        // Reset form dan tutup modal
        $this->reset(['file']);
        $this->closeModal();
    }
}
