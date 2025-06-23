<?php

namespace Database\Seeders;

use App\Models\Companie;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CompanieSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Companie::create([
            'name'          => 'Duta Sae',
            'logo'          => null,
            'address'       => 'Ngantrirejo, Malangjiwan, Kec. Colomadu, Kabupaten Karanganyar, Jawa Tengah 57177',
            'phone'         => '07685127',
            'email'         => 'admin@dutasae.com',
            'npwp'          => '12.345.678.9-012.345',
            'owner_name'    => 'Bapak Duta',
            'bank_name'     => 'Bank BRI',
            'bank_account'  => '1234567890',
            'slogan'        => 'Toko Bangunan Terpercaya',
            'description'   => 'Toko Bangunan Terpercaya, Terjangkau dan Aman',
            'address_link'          => 'https://maps.app.goo.gl/9M6hKktfZYWR1tzW8',
         
        ]);
    }
}
