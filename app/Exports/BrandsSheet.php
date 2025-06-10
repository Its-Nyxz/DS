<?php

namespace App\Exports;

use App\Models\Brand;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class BrandsSheet implements FromCollection, WithHeadings, WithTitle
{
    public function collection()
    {
        return Brand::all(['name']); // Ambil Brand dengan slug
    }

    public function headings(): array
    {
        return ['Name']; // Header kolom untuk Brand
    }

    public function title(): string
    {
        return 'Merek'; // Nama Sheet untuk Brands
    }
}
