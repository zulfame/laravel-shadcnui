<?php

namespace Database\Seeders;

use App\Support\Modules;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

/**
 * Semua izin diturunkan dari App\Support\Modules (view/manage per entitas),
 * jadi cukup tambahkan entitas baru di Modules::MAP.
 */
class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Modules::permissions() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
    }
}
