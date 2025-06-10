<?php

namespace App\Exports;

use App\Models\Unit;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class UnitsSheet implements FromCollection, WithHeadings, WithTitle
{
    public function collection()
    {
        return Unit::all(['name', 'symbol']); // Ambil Unit dengan symbol
    }

    public function headings(): array
    {
        return ['Name', 'Symbol']; // Header kolom untuk Unit
    }

    public function title(): string
    {
        return 'Satuan'; // Nama Sheet untuk Units
    }
}
