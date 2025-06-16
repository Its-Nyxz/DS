<?php

namespace Database\Seeders;

use App\Models\Companie;
use App\Models\CompanieBanners;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CompanieBannersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ambil company pertama
        $company = Companie::first();

        if (!$company) {
            $this->command->warn('No company found. Run CompanySeeder first.');
            return;
        }

        // Data banner dummy
        $banners = [
            [
                'title' => 'Promo Semen Murah!',
                'image_path' => 'banner1.jpg',
            ],
            [
                'title' => 'Diskon Cat Tembok!',
                'image_path' => 'banner2.jpg',
            ],
            [
                'title' => 'Gratis Ongkir Akhir Pekan!',
                'image_path' => 'banner3.jpg',
            ],
        ];

        foreach ($banners as $banner) {
            CompanieBanners::create([
                'companie_id' => $company->id,
                'title' => $banner['title'],
                'image_path' => $banner['image_path'],
            ]);
        }

        $this->command->info('Company banners seeded!');
    }
}
