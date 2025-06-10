<?php

namespace App\Exports;

use App\Models\Unit;
use App\Models\Brand;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class ItemsSheet implements FromCollection, WithHeadings, WithEvents, WithTitle
{
    public function collection()
    {
        return collect([
            ['Item A', '', '', 10],  // Contoh data
            ['Item B', '', '', 20]   // Contoh data
        ]);
    }

    public function headings(): array
    {
        return ['name', 'unit', 'brand', 'min_stock'];
    }

    public function afterSheet(AfterSheet $event)
    {
        // Ambil slug unit dan brand dari database
        $unitSlugs = Unit::pluck('slug')->toArray();  // Ambil data Slug Unit
        $brandSlugs = Brand::pluck('slug')->toArray();  // Ambil data Slug Brand

        // Menggunakan implode untuk gabungkan unit slugs menjadi string
        $unitRange = implode(',', $unitSlugs);
        $brandRange = implode(',', $brandSlugs);

        // Menambahkan dropdown untuk kolom Unit Slug (B2:B1000)
        $event->sheet->getDelegate()->getDataValidation('B2:B1000')
            ->setType(DataValidation::TYPE_LIST)   // Validasi List
            ->setAllowBlank(true)                   // Allow blank
            ->setShowDropDown(true)                 // Show dropdown
            ->setFormula1('"' . $unitRange . '"');  // Menyertakan unit slugs dalam dropdown

        // Menambahkan dropdown untuk kolom Brand Slug (C2:C1000)
        $event->sheet->getDelegate()->getDataValidation('C2:C1000')
            ->setType(DataValidation::TYPE_LIST)   // Validasi List
            ->setAllowBlank(true)                   // Allow blank
            ->setShowDropDown(true)                 // Show dropdown
            ->setFormula1('"' . $brandRange . '"');  // Menyertakan brand slugs dalam dropdown
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $this->afterSheet($event);  // Menambahkan dropdown untuk Unit ID dan Brand ID
            },
        ];
    }

    public function title(): string
    {
        return 'Barang'; // Nama Sheet untuk Items
    }
}
