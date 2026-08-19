<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Cetakan data awal proyek. Idempoten: aman dijalankan berulang kali.
 * Setiap domain punya seeder sendiri agar mudah diubah / dijalankan terpisah:
 *   php artisan db:seed --class=MenuSeeder
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            UserSeeder::class,
            SettingSeeder::class,
            MenuSeeder::class,
        ]);
    }
}
