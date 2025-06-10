<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;

class TemplateBrandExport implements FromArray
{
    /**
     * Menyediakan data untuk template Excel.
     */
    public function array(): array
    {
        return [
            ['name'], // Header kolom
            ['Krakatau Steel'], // Contoh data
        ];
    }
}
