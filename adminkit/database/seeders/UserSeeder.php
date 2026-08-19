<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Akun bawaan. Kata sandi HANYA disetel saat akun dibuat pertama kali,
 * jadi menjalankan seeder ulang tidak mengubah kata sandi yang sudah dipakai.
 */
class UserSeeder extends Seeder
{
    /** [nama, username, email, telepon, kata sandi awal, peranan] */
    private const USERS = [
        ['IT Support', 'superadmin', 'sa@bprbangunarta.co.id', null, 'SA@4dm1n', RoleName::SuperAdmin->value],
    ];

    public function run(): void
    {
        foreach (self::USERS as [$name, $username, $email, $phone, $password, $role]) {
            $user = User::firstOrNew(['email' => $email]);

            $user->fill([
                'name' => $name,
                'username' => $username,
                'phone' => $phone,
                'is_active' => true,
                'email_verified_at' => $user->email_verified_at ?? now(),
            ]);

            if (! $user->exists) {
                $user->password = $password;
            }

            $user->save();
            $user->syncRoles([$role]);
        }
    }
}
