<?php

namespace App\Imports;

use App\Models\Supplier;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SuppliersImport implements ToModel, WithHeadingRow
{
    /**
     * Mengkonversi setiap baris data menjadi model Unit.
     */
    public function model(array $row)
    {
        // Log setiap baris untuk debugging
        Log::info('Processing row: ', $row);

        // Membuat slug
        $slug = Str::slug($row['name']);

        // Pastikan nomor telepon diperlakukan sebagai string
        $phone = (string) $row['phone'];

        if (strpos($phone, '+62') !== 0) {
            // Jika nomor telepon tidak diawali dengan +62, tambahkan +62
            $phone = '+62' . ltrim($phone, '0');  // Menghapus angka 0 di awal dan menambahkan +62
        }

        // Proses update atau create supplier berdasarkan slug
        return Supplier::updateOrCreate(
            ['slug' => $slug],  // Kondisi pencarian berdasarkan slug
            [
                'name' => $row['name'],
                'phone' => $phone,  // Menyimpan nomor telepon sebagai string
                'address' => $row['address'],
            ]
        );
    }
}
