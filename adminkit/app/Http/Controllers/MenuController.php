<?php

namespace App\Http\Controllers;

use App\Http\Requests\Menu\ReorderMenuRequest;
use App\Http\Requests\Menu\StoreMenuRequest;
use App\Http\Requests\Menu\UpdateMenuRequest;
use App\Models\ActivityLog;
use App\Models\Menu;
use App\Support\Modules;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;

class MenuController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Menus', [
            'areas' => collect(Menu::AREAS)
                ->map(fn (string $label, string $area) => [
                    'id' => $area,
                    'label' => $label,
                    'items' => Menu::tree($area),
                ])->values()->all(),
            'permissionOptions' => Permission::orderBy('name')->pluck('name')
                ->map(fn (string $name) => ['value' => $name, 'label' => $name])
                ->prepend(['value' => '', 'label' => 'Tanpa Izin (Semua Pengguna)'])
                ->all(),
            'modules' => Modules::MAP,
            'maxDepth' => Menu::MAX_DEPTH,
        ]);
    }

    public function store(StoreMenuRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['sort'] = (int) Menu::where('parent_id', $data['parent_id'] ?? null)->max('sort') + 1;

        $menu = Menu::create($data);

        ActivityLog::record("Menambah menu {$menu->label}", 'Menu', 'success', $menu);

        return back()->with('success', "Menu {$menu->label} ditambahkan.");
    }

    public function update(UpdateMenuRequest $request, Menu $menu): RedirectResponse
    {
        $before = $menu->only(['label', 'href', 'icon', 'permission', 'parent_id', 'area', 'is_active']);
        $menu->update($request->validated());

        ActivityLog::record(
            "Memperbarui menu {$menu->label}",
            'Menu',
            'info',
            $menu,
            ['sebelum' => $before, 'sesudah' => $menu->only(array_keys($before))],
        );

        return back()->with('success', "Menu {$menu->label} diperbarui.");
    }

    public function destroy(Menu $menu): RedirectResponse
    {
        $label = $menu->label;
        $menu->delete();

        ActivityLog::record("Menghapus menu {$label}", 'Menu', 'danger');

        return back()->with('success', "Menu {$label} dihapus beserta submenunya.");
    }

    /** Simpan susunan pohon (urutan + induk) hasil geseran. */
    public function reorder(ReorderMenuRequest $request): RedirectResponse
    {
        foreach ($request->validated()['nodes'] as $index => $node) {
            Menu::whereKey($node['id'])->update([
                'parent_id' => $node['parent_id'] ?: null,
                'area' => $node['area'],
                'sort' => $index,
            ]);
        }

        ActivityLog::record('Menyusun ulang menu', 'Menu', 'info');

        return back()->with('success', 'Susunan menu disimpan.');
    }
}
