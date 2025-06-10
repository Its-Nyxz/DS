<?php

namespace App\Imports;

use App\Models\Brand;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class BrandsImport implements ToModel, WithHeadingRow
{
    /**
     * Mengkonversi setiap baris data menjadi model Unit.
     */
    public function model(array $row)
    {
        // Log setiap baris untuk debugging
        // Log::info('Processing row: ', $row);

        // // Periksa apakah baris memiliki kolom name_satuan dan symbol
        // if (!isset($row['name'])) {
        //     Log::error('Missing columns in row: ', $row);
        //     return null; // Abaikan baris jika kolom tidak ada
        // }

        // // Periksa apakah kolom name kosong
        // if (empty($row['name'])) {
        //     Log::error('Empty field(s) in row: ', $row);
        //     return null; // Abaikan baris jika ada kolom kosong
        // }

        // Cek jika baris pertama adalah Krakatau Steel, jika ya, abaikan (jangan diproses)
        if ($row['name'] == 'Krakatau Steel') {
            Log::info('Skipping row with name "Krakatau Steel"');
            return null; // Tidak memproses baris ini
        }

        // Membuat slug berdasarkan nama satuan
        $slug = Str::slug($row['name']);

        // Proses update atau create unit berdasarkan slug
        return Brand::updateOrCreate(
            ['slug' => $slug],  // Kondisi pencarian berdasarkan slug
            [
                'name' => $row['name'], // Menyimpan nama satuan
            ]
        );
    }
}
