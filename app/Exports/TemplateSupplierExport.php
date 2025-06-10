<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;

class TemplateSupplierExport implements FromArray, WithColumnFormatting
{
    /**
     * Menyediakan data untuk template Excel.
     */
    public function array(): array
    {
        return [
            ['name', 'phone', 'address'], // Header kolom
            ['Toko Baja Murni', '+6281234567890', 'Jl. Besi No. 1'], // Contoh data
        ];
    }

    /**
     * Format kolom pada Excel.
     *
     * @return array
     */
    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_TEXT, // Mengatur kolom 'B' (nomor telepon) sebagai teks
        ];
    }
}
