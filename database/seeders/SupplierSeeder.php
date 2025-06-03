<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $suppliers = [
            ['name' => 'Toko Baja Murni', 'phone' => '081234567890', 'address' => 'Jl. Besi No. 1'],
            ['name' => 'PT. Bangun Jaya', 'phone' => '081987654321', 'address' => 'Jl. Konstruksi No. 45'],
            ['name' => 'UD Mitra Bangunan', 'phone' => '082112233445', 'address' => 'Jl. Pembangunan No. 88'],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::firstOrCreate(['name' => $supplier['name']], $supplier);
        }
    }
}
