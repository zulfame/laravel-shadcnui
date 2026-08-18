<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Item menu sidebar. Maksimal 3 tingkat (grup → menu → submenu).
 * Item tanpa `href` diperlakukan sebagai grup yang dapat dibuka/tutup.
 */
class Menu extends Model
{
    public const AREAS = ['member' => 'Member Area', 'admin' => 'Administrator'];

    public const MAX_DEPTH = 3;

    protected $fillable = ['parent_id', 'area', 'label', 'href', 'icon', 'permission', 'sort', 'is_active'];

    protected $casts = ['is_active' => 'boolean', 'sort' => 'integer'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort');
    }

    /** Pohon menu lengkap untuk halaman pengelolaan. */
    public static function tree(?string $area = null): array
    {
        $all = self::orderBy('sort')->get();

        $build = function (?int $parentId) use (&$build, $all, $area) {
            return $all
                ->filter(fn (self $m) => $m->parent_id === $parentId && ($area === null || $m->area === $area))
                ->map(fn (self $m) => [
                    'id' => $m->id,
                    'parent_id' => $m->parent_id,
                    'area' => $m->area,
                    'label' => $m->label,
                    'href' => $m->href,
                    'icon' => $m->icon,
                    'permission' => $m->permission,
                    'is_active' => $m->is_active,
                    'children' => $build($m->id),
                ])
                ->values()->all();
        };

        return $build(null);
    }

    /** Pohon menu yang boleh dilihat pengguna: aktif + izin terpenuhi. */
    public static function treeFor(?User $user): array
    {
        if (! $user) {
            return [];
        }

        $granted = $user->getAllPermissions()->pluck('name');

        $filter = function (array $items) use (&$filter, $granted) {
            return collect($items)
                ->filter(fn (array $item) => $item['is_active']
                    && (! $item['permission'] || $granted->contains($item['permission'])))
                ->map(fn (array $item) => [...$item, 'children' => $filter($item['children'])])
                // Grup tanpa anak yang lolos filter tidak perlu ditampilkan.
                ->reject(fn (array $item) => ! $item['href'] && empty($item['children']))
                ->values()->all();
        };

        return collect(self::AREAS)
            ->map(fn (string $label, string $area) => [
                'id' => $area,
                'label' => $label,
                'items' => $filter(self::tree($area)),
            ])
            ->values()->all();
    }
}
