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
    /** [nama, username, email, telepon, kantor, kata sandi awal, peranan] */
    private const USERS = [
        ['IT Support', 'superadmin', 'sa@bprbangunarta.co.id', null, 'Kantor Pusat', 'SA@4dm1n', RoleName::SuperAdmin->value],
    ];

    public function run(): void
    {
        foreach (self::USERS as [$name, $username, $email, $phone, $office, $password, $role]) {
            $user = User::withTrashed()->firstOrNew(['email' => $email]);

            $user->fill([
                'name' => $name,
                'username' => $username,
                'phone' => $phone,
                'office' => $office,
                'email_verified_at' => $user->email_verified_at ?? now(),
            ]);
            $user->deleted_at = null;

            if (! $user->exists) {
                $user->password = $password;
            }

            $user->save();
            $user->setRoleName($role);
        }
    }
}
