<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Idempoten: aman dijalankan berulang kali.
     */
    public function run(): void
    {
        $role = Role::findOrCreate(RoleName::SuperAdmin->value, 'web');

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
    }
}
