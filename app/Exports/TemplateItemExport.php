<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class TemplateItemExport implements WithMultipleSheets
{
    /**
     * Menyediakan beberapa sheet untuk export.
     *
     * @return array
     */
    public function sheets(): array
    {
        return [
            // // Sheet untuk Units
            // new UnitsSheet(),

            // // Sheet untuk Brands
            // new BrandsSheet(),

            // Sheet untuk Items
            new ItemsSheet(),
        ];
    }
}
