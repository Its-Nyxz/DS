<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Companie;
use App\Models\CompanieBanners;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

class CompanyBannerManager extends Component
{
    use WithFileUploads;

    public $banner_image, $title;
    public $company;
    public $banners = [];

    public function mount()
    {
        $this->company = Companie::first();
        $this->banners = $this->company->banners;
    }

    public function uploadBanner()
    {
        $this->validate([
            'banner_image' => 'image|max:2048',
        ]);

        $filename = uniqid('banner_') . '.' . $this->banner_image->getClientOriginalExtension();
        Storage::disk('public')->putFileAs('company', $this->banner_image, $filename);

        $this->company->banners()->create([
            'image_path' => $filename,
            'title' => $this->title,
        ]);

        $this->reset(['banner_image', 'title']);
        $this->banners = $this->company->banners()->latest()->get();

        $this->dispatch('alert-success', ['message' => 'Banner ditambahkan.']);
    }

    public function deleteBanner($id)
    {
        $banner = CompanieBanners::findOrFail($id);
        Storage::disk('public')->delete('company/' . $banner->image_path);
        $banner->delete();

        $this->banners = $this->company->banners()->latest()->get();
        $this->dispatch('alert-success', ['message' => 'Banner dihapus.']);
    }
    public function render()
    {
        return view('livewire.company-banner-manager');
    }
}
