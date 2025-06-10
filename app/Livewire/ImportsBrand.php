<?php

namespace App\Livewire;

use Livewire\Component;
use App\Imports\BrandsImport;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;

class ImportsBrand extends Component
{
    use WithFileUploads;

    public $file;
    public $isModalOpen = false;

    // Validasi file yang diupload
    protected $rules = [
        'file' => 'required|mimes:xlsx,xls,csv|max:2048', // Max size 2MB
    ];


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
        if (strpos(strtolower($fileName), 'merk') === false) {
            $this->dispatch('alert-error', ['message' => 'Nama file harus mengandung kata "merk".']);
            return;
        }


        try {
            // Import data dari file Excel
            Excel::import(new BrandsImport, $this->file);

            // Pesan sukses setelah impor selesai
            $this->dispatch('alert-success', ['message' => 'Data merk berhasil diimpor.']);
            $this->dispatch('refreshDataBrand');
        } catch (\Exception $e) {
            // Tangani error jika terjadi kesalahan saat impor
            $this->dispatch('alert-error', ['message' => 'Terjadi kesalahan saat mengimpor data.']);
        }

        // Reset form dan tutup modal
        $this->reset(['file']);
        $this->closeModal();
    }

    public function render()
    {
        return view('livewire.imports-brand');
    }
}
