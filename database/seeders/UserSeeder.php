<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pemilik = User::create([
            'name' => 'Pemilik',
            'slug' => Str::slug('Pemilik'),
            'email' => 'pemilik@gmail.com',
            'password' => Hash::make('12345678'),
        ]);
        $pemilik->assignRole('pemilik');

        $admin = User::create([
            'name' => 'Admin',
            'slug' => Str::slug('Admin'),
            'email' => 'admin@gmail.com',
            'password' => Hash::make('12345678'),
        ]);
        $admin->assignRole('admin');

        $pegawai = User::create([
            'name' => 'Pegawai',
            'slug' => Str::slug('Pegawai'),
            'email' => 'pegawai@gmail.com',
            'password' => Hash::make('12345678'),
        ]);
        $pegawai->assignRole('pegawai');
    }
}
