<?php

namespace App\Support;

/**
 * Daftar modul & kemampuan (ability) yang membentuk nama izin: `<modul>.<ability>`.
 * Satu-satunya sumber kebenaran untuk seeder izin maupun matriks di halaman
 * Kelola Peranan.
 */
class Modules
{
    public const ABILITY_LABELS = [
        'view' => 'Lihat',
        'manage' => 'Kelola',
    ];

    public const MAP = [
        'dashboard' => ['label' => 'Dashboard', 'abilities' => ['view']],
        'users' => ['label' => 'Kelola Pengguna', 'abilities' => ['view', 'manage']],
        'permissions' => ['label' => 'Perizinan', 'abilities' => ['view', 'manage']],
        'roles' => ['label' => 'Kelola Peranan', 'abilities' => ['view', 'manage']],
        'appearance' => ['label' => 'Penampilan', 'abilities' => ['view', 'manage']],
        'activity' => ['label' => 'Audit Trail', 'abilities' => ['view', 'manage']],
        'menus' => ['label' => 'Menu Sidebar', 'abilities' => ['view', 'manage']],
        'profile' => ['label' => 'Profil Pengguna', 'abilities' => ['view']],
    ];

    /** Semua nama izin, mis. ['dashboard.view', 'users.view', ...]. */
    public static function permissions(): array
    {
        $names = [];
        foreach (self::MAP as $key => $module) {
            foreach ($module['abilities'] as $ability) {
                $names[] = "{$key}.{$ability}";
            }
        }

        return $names;
    }

    /** Struktur matriks untuk antarmuka. */
    public static function matrix(): array
    {
        return collect(self::MAP)->map(fn ($module, $key) => [
            'key' => $key,
            'label' => $module['label'],
            'abilities' => collect($module['abilities'])->map(fn ($ability) => [
                'name' => "{$key}.{$ability}",
                'label' => self::ABILITY_LABELS[$ability] ?? $ability,
            ])->all(),
        ])->values()->all();
    }
}
