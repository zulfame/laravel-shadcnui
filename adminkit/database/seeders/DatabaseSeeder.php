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
                'username' => 'zulfame',
                'phone' => '082320099971',
                'is_active' => true,
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ]
        );

        if (! $user->hasRole($role)) {
            $user->assignRole($role);
        }

        // Peranan contoh berhak-akses terbatas, untuk mencoba matriks izin.
        $staff = Role::findOrCreate('Staf', 'web');
        $staff->syncPermissions(['dashboard.view', 'profile.view']);

        // Data contoh agar pencarian & paginasi sisi server dapat dicoba.
        if (User::count() < 25) {
            User::factory()->count(24)->create()->each(fn (User $u) => $u->assignRole($staff));
        }

        // Nilai default object storage (dapat diubah di halaman Penyimpanan).
        $storageDefaults = [
            'storage_driver' => 'local',
            's3_endpoint' => 'https://nos.wjv-1.neo.id',
            's3_key' => '2b6bd2f6e7906d182251',
            's3_secret' => 'xLapXGeuT8+YSzDa03ba2PkQw65IDFcIwgo5HoOy',
            's3_region' => 'idn',
            's3_bucket' => 'bpr-assets',
            's3_path' => '',
            's3_path_style' => '1',
            's3_public_url' => '',
        ];

        foreach ($storageDefaults as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
