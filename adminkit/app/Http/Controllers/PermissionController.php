<?php

namespace App\Http\Controllers;

use App\Http\Requests\Permission\BulkPermissionRequest;
use App\Http\Requests\Permission\GeneratePermissionRequest;
use App\Http\Requests\Permission\StorePermissionRequest;
use App\Models\ActivityLog;
use App\Support\Excel;
use App\Support\Modules;
use App\Support\Notify;
use App\Support\TableQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PermissionController extends Controller
{
    private const SORTABLE = ['name', 'guard_name', 'created_at'];

    public function index(Request $request): Response
    {
        $search = TableQuery::search($request);
        $sort = TableQuery::sort($request, self::SORTABLE, 'name');
        $dir = TableQuery::direction($request);
        $entity = TableQuery::filter($request, 'entity');

        $permissions = Permission::query()
            ->withCount('roles')
            ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->when($entity !== '', fn ($q) => $q->where('name', 'like', "{$entity}.%"))
            ->orderBy($sort, $dir)
            ->paginate(TableQuery::perPage($request))
            ->withQueryString();

        return Inertia::render('Permissions', [
            'permissions' => [
                'data' => collect($permissions->items())->map(fn (Permission $p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'entity' => str($p->name)->before('.')->value(),
                    'ability' => str($p->name)->after('.')->value(),
                    'guard_name' => $p->guard_name,
                    'roles_count' => $p->roles_count,
                    'locked' => in_array($p->name, Modules::permissions(), true),
                ])->all(),
                'meta' => TableQuery::meta($permissions),
            ],
            'filters' => ['search' => $search, 'sort' => $sort, 'dir' => $dir, 'entity' => $entity],
            'abilityOptions' => GeneratePermissionRequest::ABILITIES,
            'entityOptions' => Permission::query()
                ->pluck('name')
                ->map(fn ($name) => str($name)->before('.')->value())
                ->unique()->sort()->values()
                ->map(fn ($e) => ['value' => $e, 'label' => $e])
                ->prepend(['value' => 'all', 'label' => 'Semua Entitas'])
                ->all(),
        ]);
    }

    /** Buat izin standar sekaligus untuk sebuah entitas. */
    public function generate(GeneratePermissionRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $created = [];

        foreach ($data['abilities'] as $ability) {
            $name = "{$data['entity']}.{$ability}";

            if (Permission::where('name', $name)->where('guard_name', 'web')->exists()) {
                continue;
            }

            Permission::create(['name' => $name, 'guard_name' => 'web']);
            $created[] = $name;
        }

        $this->flushCache();
        $skipped = count($data['abilities']) - count($created);

        if ($created === []) {
            return back()->with('error', 'Semua izin untuk entitas tersebut sudah ada.');
        }

        ActivityLog::record(
            count($created)." izin dibuat untuk entitas {$data['entity']}",
            'Perizinan',
            'success',
            context: ['izin' => implode(', ', $created), 'dilewati' => $skipped],
        );

        Notify::toPermission(
            permission: 'permissions.view',
            title: 'Izin standar dibuat',
            module: 'Perizinan',
            body: "{$data['entity']} · ".count($created).' izin',
            url: '/permissions',
            level: 'success',
        );

        return back()->with(
            'success',
            count($created).' izin dibuat'.($skipped ? ", {$skipped} dilewati." : '.')
        );
    }

    /** Unduh Excel mengikuti filter aktif. */
    public function export(Request $request): StreamedResponse
    {
        $search = TableQuery::search($request);
        $entity = TableQuery::filter($request, 'entity');

        $rows = Permission::query()
            ->withCount('roles')
            ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->when($entity !== '', fn ($q) => $q->where('name', 'like', "{$entity}.%"))
            ->orderBy(TableQuery::sort($request, self::SORTABLE, 'name'), TableQuery::direction($request))
            ->cursor()
            ->map(fn (Permission $p) => [
                $p->name,
                str($p->name)->before('.')->value(),
                str($p->name)->after('.')->value(),
                $p->guard_name,
                $p->roles_count,
            ]);

        ActivityLog::record('Mengekspor daftar izin (Excel)', 'Perizinan', 'info');

        return Excel::download(
            Excel::filename('perizinan'),
            ['Nama Izin', 'Entitas', 'Aksi', 'Guard', 'Jumlah Peranan'],
            $rows,
        );
    }

    public function store(StorePermissionRequest $request): RedirectResponse
    {
        $permission = Permission::create(['name' => $request->validated()['name'], 'guard_name' => 'web']);
        $this->flushCache();

        ActivityLog::record(
            "Menambah izin {$permission->name}",
            'Perizinan',
            'success',
            changes: ActivityLog::snapshotOf($permission),
        );

        Notify::toPermission(
            permission: 'permissions.view',
            title: 'Izin baru dibuat',
            module: 'Perizinan',
            body: $permission->name,
            url: '/permissions',
            level: 'success',
        );

        return back()->with('success', "Izin {$permission->name} ditambahkan.");
    }

    public function update(StorePermissionRequest $request, Permission $permission): RedirectResponse
    {
        abort_if($this->isLocked($permission), 403, 'Izin inti tidak dapat diubah.');

        $before = $permission->name;
        $permission->update(['name' => $request->validated()['name']]);
        $this->flushCache();

        ActivityLog::record(
            "Memperbarui izin {$permission->name}",
            'Perizinan',
            'info',
            changes: ['name' => ['old' => $before, 'new' => $permission->name]],
        );

        return back()->with('success', 'Izin diperbarui.');
    }

    public function destroy(Permission $permission): RedirectResponse
    {
        abort_if($this->isLocked($permission), 403, 'Izin inti tidak dapat dihapus.');

        $name = $permission->name;
        $permission->delete();
        $this->flushCache();

        ActivityLog::record("Menghapus izin {$name}", 'Perizinan', 'danger');

        return back()->with('success', "Izin {$name} dihapus.");
    }

    /** Hapus massal; izin inti (bawaan modul) dilewati. */
    public function bulkDestroy(BulkPermissionRequest $request): RedirectResponse
    {
        $permissions = Permission::whereIn('id', $request->validated()['ids'])->get();
        $deletable = $permissions->reject(fn (Permission $p) => $this->isLocked($p));
        $skipped = $permissions->count() - $deletable->count();

        if ($deletable->isEmpty()) {
            return back()->with('error', 'Tidak ada izin yang dapat dihapus (semuanya izin inti).');
        }

        $names = $deletable->pluck('name')->implode(', ');
        Permission::whereIn('id', $deletable->modelKeys())->delete();
        $this->flushCache();

        ActivityLog::record(
            "Menghapus {$deletable->count()} izin secara massal",
            'Perizinan',
            'danger',
            context: ['izin' => $names, 'dilewati' => $skipped],
        );

        return back()->with(
            'success',
            "{$deletable->count()} izin dihapus".($skipped ? ", {$skipped} dilewati." : '.')
        );
    }

    private function isLocked(Permission $permission): bool
    {
        return in_array($permission->name, Modules::permissions(), true);
    }

    private function flushCache(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
