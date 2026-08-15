<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\Setting;
use App\Models\User;
use App\Support\Modules;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    /**
     * Idempoten: aman dijalankan berulang kali.
     */
    public function run(): void
    {
        foreach (Modules::permissions() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $role = Role::findOrCreate(RoleName::SuperAdmin->value, 'web');
        $role->syncPermissions(Modules::permissions());

        $user = User::updateOrCreate(
            ['email' => 'zulfadlirizal@gmail.com'],
            [
                'name' => 'Zulfadli Rizal',
                'username' => '309011221',
                'phone' => '082320099971',
                'is_active' => true,
                'email_verified_at' => now(),
                'password' => Hash::make('1'),
            ]
        );

        if (! $user->hasRole($role)) {
            $user->assignRole($role);
        }

        // Peranan contoh berhak-akses terbatas, untuk mencoba matriks izin.
        $staff = Role::findOrCreate('Guest');
        $staff->syncPermissions(['dashboard.view', 'profile.view']);

        // Data contoh agar pencarian & paginasi sisi server dapat dicoba.
        if (User::count() < 25) {
            User::factory()->count(24)->create()->each(fn (User $u) => $u->assignRole($staff));
        }

        // Modul Penyimpanan dihapus: kredensial object storage kini dari .env saja.
        Setting::whereIn('key', [
            'storage_driver', 's3_endpoint', 's3_key', 's3_secret',
            's3_region', 's3_bucket', 's3_path', 's3_path_style', 's3_public_url',
        ])->delete();
        Permission::whereIn('name', ['storage.view', 'storage.manage'])->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
