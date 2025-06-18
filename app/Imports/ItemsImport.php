<?php

namespace App\Imports;

use App\Models\Item;
use App\Models\Unit;
use App\Models\Brand;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ItemsImport implements ToModel, WithHeadingRow
{
    /**
     * Mengkonversi setiap baris data menjadi model Item.
     */
    public function model(array $row)
    {
        // Pastikan hanya sheet Barang yang dibaca
        if (isset($row['name']) && isset($row['unit']) && isset($row['brand'])) {

            // Validasi Unit berdasarkan slug
            $unitSlug = Str::slug($row['unit']); // Membuat slug dari nama unit
            $unit = Unit::where('slug', $unitSlug)->first();
            if (!$unit) {
                // Jika unit tidak ditemukan
                throw new \Exception('Unit tidak valid: ' . $row['unit']);
            }

            // Validasi Brand berdasarkan slug
            $brandSlug = Str::slug($row['brand']); // Membuat slug dari nama brand
            $brand = Brand::where('slug', $brandSlug)->first();
            if (!$brand) {
                // Jika brand tidak ditemukan
                throw new \Exception('Brand tidak valid: ' . $row['brand']);
            }

            // Cek jika item sudah ada berdasarkan kombinasi name, unit_id, dan brand_id
            $item = Item::where('name', $row['name'])
                ->where('unit_id', $unit->id)
                ->where('brand_id', $brand->id)
                ->first();

            // Jika item sudah ada, biarkan SKU tetap
            if ($item) {
                $item->update([
                    'min_stock' => $row['min_stock'] ?? 0,  // Default min_stock if not provided
                    'harga_jual' => $row['harga_jual'] ?? 0,  // Default harga_jual if not provided
                    'stock_awal' => $row['stock_awal'] ?? 0,  // Default stok_awal if not provided
                ]);
                return $item;
            } else {
                // If item doesn't exist, create new item with SKU
                return Item::create([
                    'name' => $row['name'],
                    'sku' => $this->generateSKU($row['name']),  // Automatically generate SKU
                    'unit_id' => $unit->id,
                    'brand_id' => $brand->id,
                    'min_stock' => $row['min_stock'] ?? 0,      // Default min_stock if not available
                    'harga_jual' => $row['harga_jual'] ?? 0,    // Default harga_jual if not available
                    'stock_awal' => $row['stock_awal'] ?? 0,      // Default stok_awal if not available
                ]);
            }
        }

        return null;
    }

    /**
     * Menghasilkan SKU berdasarkan nama item
     */
    public function generateSKU($name)
    {
        $lastId = Item::latest('id')->value('id') ?? 0;
        $prefix = strtoupper(Str::slug(Str::words($name, 1, '')));
        return 'BRG-' . strtoupper(substr($prefix, 0, 3)) . str_pad($lastId + 1, 4, '0', STR_PAD_LEFT);
    }
}
