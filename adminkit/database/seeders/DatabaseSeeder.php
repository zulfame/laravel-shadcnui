<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\Menu;
use App\Models\Setting;
use App\Models\User;
use App\Support\Modules;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Cetakan data awal proyek — cerminan data produksi saat ini
 * (1 pengguna, 2 peranan, 12 izin, dan seluruh setelan branding/SEO).
 * Idempoten: aman dijalankan berulang kali, tidak menambah data contoh.
 */
class DatabaseSeeder extends Seeder
{
    /** Izin per peranan. */
    private const ROLE_PERMISSIONS = [
        'Guest' => ['dashboard.view', 'profile.view'],
    ];

    /** Setelan aplikasi (branding, SEO, preferensi tampilan). */
    private const SETTINGS = [
        'app_name' => 'JKV Studio',
        'app_url' => null,
        'brand_color' => '#0F0F0F',
        'brand_initials' => 'JKV',
        'canonical_url' => '',
        'company' => 'AdminKit',
        'date_format' => 'DD/MM/YYYY',
        'favicon' => null,
        'footer_text' => '@ 2026 CV Jendela Kreasi Visual. All Rights Reserved.',
        'language' => 'id',
        'logo_dark' => null,
        'logo_light' => null,
        'meta_description' => 'CV Jendela Kreasi Visual (JKV) — Digital Creative Agency spesialis konten media sosial, pembuatan website, dan aplikasi mobile berkualitas.',
        'meta_keywords' => 'admin panel, laravel, inertia, vue',
        'meta_title' => 'JKV Studio',
        'og_description' => 'CODEX adalah platform integrasi data inti untuk monitoring, analisis, dan pertukaran data nasabah BPR Bangunarta.',
        'og_image' => null,
        'og_title' => 'CODEX: Core Data Exchange',
        'permission_entity_order' => '["dashboard","profile","permissions","roles","users","appearance","activity"]',
        'search_indexable' => '0',
        'support_email' => 'studio@jkv.co.id',
        'tagline' => 'Jendela Baru',
        'thumbnail' => 'branding/Evm4ywAi7xnIlGuTyYvTDAuTSwLSOZErKyoKBFDP.jpg',
        'timezone' => 'Asia/Jakarta',
    ];

    /** Menu sidebar bawaan: [area, label, href, icon, permission, anak-anak]. */
    private const MENUS = [
        ['member', 'Dashboard', '/', 'LayoutDashboard', 'dashboard.view', []],
        ['member', 'Level 1', null, 'Folder', null, [
            ['Level 2', null, 'FolderOpen', null, [
                ['Level 3', '/level-3', 'FileText', null, []],
            ]],
            ['Level 2 Tautan', '/level-2', 'FileText', null, []],
        ]],
        ['admin', 'Perizinan', '/permissions', 'KeyRound', 'permissions.view', []],
        ['admin', 'Peranan', '/roles', 'ShieldCheck', 'roles.view', []],
        ['admin', 'Pengguna', '/users', 'Users2', 'users.view', []],
        ['admin', 'Penampilan', '/appearance', 'Palette', 'appearance.view', []],
        ['admin', 'Menu Sidebar', '/menus', 'ListTree', 'menus.view', []],
        ['admin', 'Audit Trail', '/audit-trail', 'ScrollText', 'activity.view', []],
    ];

    /** Idempoten: item dikenali dari kombinasi area + label + induk. */
    private function seedMenus(array $items, string $area, ?int $parentId = null): void
    {
        foreach ($items as $index => $item) {
            [$label, $href, $icon, $permission, $children] = $parentId === null
                ? [$item[1], $item[2], $item[3], $item[4], $item[5]]
                : $item;

            $menu = Menu::updateOrCreate(
                ['area' => $area, 'parent_id' => $parentId, 'label' => $label],
                ['href' => $href, 'icon' => $icon, 'permission' => $permission, 'sort' => $index, 'is_active' => true],
            );

            $this->seedMenus($children, $area, $menu->id);
        }
    }

    public function run(): void
    {
        foreach (Modules::permissions() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $superAdmin = Role::findOrCreate(RoleName::SuperAdmin->value, 'web');
        $superAdmin->syncPermissions(Modules::permissions());

        foreach (self::ROLE_PERMISSIONS as $name => $permissions) {
            Role::findOrCreate($name, 'web')->syncPermissions($permissions);
        }

        // Kata sandi disimpan sebagai hash apa adanya agar kredensial yang
        // sedang dipakai tidak berubah saat seeder dijalankan ulang.
        $user = User::updateOrCreate(
            ['email' => 'studio@jkv.co.id'],
            [
                'name' => 'Super Admin',
                'username' => 'jkv',
                'phone' => '082320099971',
                'avatar' => null,
                'is_active' => true,
                'email_verified_at' => now(),
                'password' => 'JKV@4dm1n',
            ]
        );

        $user->syncRoles([$superAdmin->name]);

        foreach (self::SETTINGS as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        foreach (array_keys(Menu::AREAS) as $area) {
            $this->seedMenus(array_values(array_filter(self::MENUS, fn (array $m) => $m[0] === $area)), $area);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
