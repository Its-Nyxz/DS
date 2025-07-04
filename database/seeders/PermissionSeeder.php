<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // List of permissions
        $permissions = [
            'view-laporan',
            'view-setting',
            'view-data',
            'view-retur',
            'view-opname',
            'create-transaksi',
            'edit-transaksi',
            'delete-transaksi',
            'approve-transaksi',
            'termin-transaksi',
            'manage-users',
            'manage-unit',
            'manage-brand',
            'manage-item',
            'manage-supplier',
            'manage-supplier-item',
            'manage-companie',
            'manage-permissions',
            'manage-arus-kas',
            'manage-kas',
            'manage-utang',
            'manage-piutang',
        ];

        // Buat permission satu per satu
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Assign permissions ke masing-masing role sesuai kebutuhan

        $pemilik = Role::where('name', 'pemilik')->first();
        $admin = Role::where('name', 'admin')->first();
        $pegawai = Role::where('name', 'pegawai')->first();

        // Contoh pemberian izin:
        $pemilik->syncPermissions([
            'view-laporan',
            'view-setting',
            'view-data',
            'view-retur',
            'view-opname',
            'create-transaksi',
            'edit-transaksi',
            'delete-transaksi',
            'approve-transaksi',
            'termin-transaksi',
            'manage-users',
            'manage-item',
            'manage-unit',
            'manage-brand',
            'manage-supplier',
            'manage-supplier-item',
            'manage-companie',
            'manage-permissions',
            'manage-kas',
            'manage-arus-kas',
            'manage-utang',
            'manage-piutang',
        ]);

        $admin->syncPermissions([
            'view-laporan',
            'view-retur',
            'view-opname',
            'create-transaksi',
            'edit-transaksi',
            'delete-transaksi',
            'view-setting',
            'manage-users',
            'manage-supplier-item',
        ]);

        $pegawai->syncPermissions([
            'view-laporan',
            'create-transaksi',
        ]);
    }
}
