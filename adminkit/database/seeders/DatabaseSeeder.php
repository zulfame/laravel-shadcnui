<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    /** Izin per modul: <modul>.view untuk melihat, <modul>.manage untuk mengubah. */
    public const PERMISSIONS = [
        'dashboard.view',
        'users.view',
        'users.manage',
        'profile.view',
    ];

    /**
     * Idempoten: aman dijalankan berulang kali.
     */
    public function run(): void
    {
        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $role = Role::findOrCreate(RoleName::SuperAdmin->value, 'web');
        $role->syncPermissions(self::PERMISSIONS);

        $user = User::updateOrCreate(
            ['email' => 'zulfadlirizal@gmail.com'],
            [
                'name' => 'Zulfadli Rizal',
                'username' => 'zulfame',
                'phone' => '082320099971',
                'office' => 'Pamanukan',
                'is_active' => true,
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ]
        );

        if (! $user->hasRole($role)) {
            $user->assignRole($role);
        }

        // Data contoh agar pencarian & paginasi sisi server dapat dicoba.
        if (User::count() < 25) {
            User::factory()->count(24)->create();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
