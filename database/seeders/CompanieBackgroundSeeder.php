<?php

namespace Database\Seeders;

use App\Models\Companie;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CompanieBackgroundSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $company = Companie::first(); // Ambil data perusahaan pertama

        // Tambahkan beberapa gambar background untuk perusahaan
        $company->backgrounds()->create([
            'image_path' => 'background1.jpg',  // Path gambar yang disimpan di folder storage/app/public/company
        ]);

        $company->backgrounds()->create([
            'image_path' => 'background2.jpg',
        ]);
    }
}
