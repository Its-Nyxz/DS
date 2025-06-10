<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;

class TemplateUnitExport implements FromArray
{
    /**
     * Menyediakan data untuk template Excel.
     */
    public function array(): array
    {
        return [
            ['name', 'symbol'], // Header kolom
            ['Kilogram', 'kg'], // Contoh data
        ];
    }
}
