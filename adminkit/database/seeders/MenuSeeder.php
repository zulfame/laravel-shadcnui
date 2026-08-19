<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;

/**
 * Menu sidebar bawaan (maks 3 tingkat).
 * Format: [label, href, icon, permission, anak-anak].
 * Idempoten: item dikenali dari kombinasi area + induk + label.
 */
class MenuSeeder extends Seeder
{
    private const MENUS = [
        'member' => [
            ['Dashboard', '/', 'LayoutDashboard', 'dashboard.view', []],
        ],
        'admin' => [
            ['Kelola Perizinan', '/permissions', 'KeyRound', 'permissions.view', []],
            ['Kelola Peranan', '/roles', 'ShieldCheck', 'roles.view', []],
            ['Kelola Pengguna', '/users', 'Users2', 'users.view', []],
            ['Penampilan UI', '/appearance', 'Palette', 'appearance.view', []],
            ['Menu Navigasi', '/menus', 'ListTree', 'menus.view', []],
            ['Object Storage', '/object-storage', 'Database', 'storage.view', []],
            ['Audit Trail Log', '/audit-trail', 'ScrollText', 'activity.view', []],
        ],
    ];

    public function run(): void
    {
        foreach (self::MENUS as $area => $items) {
            $this->seed($items, $area);
        }
    }

    private function seed(array $items, string $area, ?int $parentId = null): void
    {
        foreach ($items as $index => [$label, $href, $icon, $permission, $children]) {
            $menu = Menu::updateOrCreate(
                ['area' => $area, 'parent_id' => $parentId, 'label' => $label],
                ['href' => $href, 'icon' => $icon, 'permission' => $permission, 'sort' => $index, 'is_active' => true],
            );

            $this->seed($children, $area, $menu->id);
        }
    }
}
