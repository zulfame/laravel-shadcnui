<?php

namespace App\Http\Controllers;

use App\Enums\RoleName;
use App\Http\Requests\Role\BulkRoleRequest;
use App\Http\Requests\Role\ImportRoleRequest;
use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\SyncRolePermissionsRequest;
use App\Models\ActivityLog;
use App\Support\Notify;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Roles', [
            'roles' => Role::query()
                ->with('permissions:id,name')
                ->withCount('users')
                ->orderBy('name')
                ->get()
                ->map(fn (Role $role) => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'users_count' => $role->users_count,
                    'permissions' => $role->permissions->pluck('name')->all(),
                    'permissions_count' => $role->permissions->count(),
                    'locked' => $role->name === RoleName::SuperAdmin->value,
                ])->all(),
        ]);
    }

    public function show(Role $role): Response
    {
        return Inertia::render('RoleDetail', [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'users_count' => $role->users()->count(),
                'locked' => $role->name === RoleName::SuperAdmin->value,
                'permissions' => $role->permissions->pluck('name')->all(),
            ],
            'matrix' => $this->matrix(),
        ]);
    }

    /**
     * Izin dikelompokkan per entitas (bagian sebelum titik) beserta aksinya —
     * dipakai sebagai matriks hak akses di halaman detail peranan.
     */
    private function matrix(): array
    {
        return Permission::orderBy('name')->pluck('name')
            ->groupBy(fn (string $name) => str($name)->before('.')->value())
            ->map(fn ($names, $entity) => [
                'entity' => $entity,
                'abilities' => $names->map(fn (string $name) => [
                    'name' => $name,
                    'label' => str($name)->after('.')->replace('_', ' ')->title()->value(),
                ])->values()->all(),
            ])
            ->values()->all();
    }

    /** Simpan matriks hak akses satu peranan. */
    public function syncPermissions(SyncRolePermissionsRequest $request, Role $role): RedirectResponse
    {
        abort_if($role->name === RoleName::SuperAdmin->value, 403, 'Peranan Super Admin tidak dapat diubah.');

        $before = $role->permissions->pluck('name')->sort()->values()->all();
        $after = collect($request->validated()['permissions'])->unique()->sort()->values()->all();

        $role->syncPermissions($after);

        ActivityLog::record(
            "Memperbarui hak akses peranan {$role->name}",
            'Peranan',
            'info',
            $role,
            ['permissions' => ['old' => implode(', ', $before), 'new' => implode(', ', $after)]],
        );

        Notify::toPermission(
            permission: 'roles.view',
            title: 'Hak akses peranan diperbarui',
            module: 'Peranan',
            body: $role->name,
            url: "/roles/{$role->id}",
            level: 'info',
        );

        return back()->with('success', 'Hak akses peranan disimpan.');
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $role = Role::create(['name' => $data['name'], 'guard_name' => 'web']);

        ActivityLog::record(
            "Menambah peranan {$role->name}",
            'Peranan',
            'success',
            $role,
            ActivityLog::snapshotOf($role),
        );

        Notify::toPermission(
            permission: 'roles.view',
            title: 'Peranan baru dibuat',
            module: 'Peranan',
            body: $role->name,
            url: '/roles',
            level: 'success',
        );

        return back()->with('success', "Peranan {$role->name} ditambahkan.");
    }

    public function update(StoreRoleRequest $request, Role $role): RedirectResponse
    {
        if ($role->name === RoleName::SuperAdmin->value) {
            return back()->with('error', 'Peranan Super Admin tidak dapat diubah.');
        }

        $data = $request->validated();
        $before = $role->name;
        $role->update(['name' => $data['name']]);

        ActivityLog::record(
            "Memperbarui peranan {$role->name}",
            'Peranan',
            'info',
            $role,
            ['name' => ['old' => $before, 'new' => $role->name]],
        );

        return back()->with('success', "Peranan {$role->name} diperbarui.");
    }

    /**
     * Impor peranan dari berkas CSV: satu nama peranan per baris
     * (baris berjudul `name`/`nama` diabaikan). Nama yang sudah ada dilewati.
     */
    public function import(ImportRoleRequest $request): RedirectResponse
    {
        $rows = array_filter(array_map(
            fn ($line) => trim(str_replace(["\r", '"'], '', explode(',', $line)[0] ?? '')),
            explode("\n", (string) file_get_contents($request->file('file')->getRealPath()))
        ));

        $added = 0;
        $skipped = 0;

        foreach ($rows as $name) {
            if (in_array(mb_strtolower($name), ['name', 'nama', 'nama peranan'], true)) {
                continue;
            }

            $valid = Validator::make(['name' => $name], [
                'name' => ['required', 'string', 'min:3', 'max:50', 'regex:/^[\pL\pM0-9 .\-]+$/u', 'unique:roles,name'],
            ])->passes();

            if (! $valid) {
                $skipped++;

                continue;
            }

            Role::create(['name' => $name, 'guard_name' => 'web']);
            $added++;
        }

        ActivityLog::record(
            "Mengimpor {$added} peranan",
            'Peranan',
            'success',
            context: ['diimpor' => $added, 'dilewati' => $skipped],
        );

        return back()->with(
            $added > 0 ? 'success' : 'error',
            "{$added} peranan diimpor, {$skipped} dilewati."
        );
    }

    /** Hapus massal: peranan Super Admin dan yang masih dipakai dilewati. */
    public function bulkDestroy(BulkRoleRequest $request): RedirectResponse
    {
        $roles = Role::whereIn('id', $request->validated()['ids'])->withCount('users')->get();

        $deletable = $roles->filter(
            fn (Role $role) => $role->name !== RoleName::SuperAdmin->value && $role->users_count === 0
        );
        $skipped = $roles->count() - $deletable->count();

        if ($deletable->isEmpty()) {
            return back()->with('error', 'Tidak ada peranan yang dapat dihapus (terkunci atau masih dipakai).');
        }

        $names = $deletable->pluck('name')->implode(', ');
        Role::whereIn('id', $deletable->modelKeys())->delete();

        ActivityLog::record(
            "Menghapus {$deletable->count()} peranan secara massal",
            'Peranan',
            'danger',
            context: ['peranan' => $names, 'dilewati' => $skipped],
        );

        Notify::toPermission(
            permission: 'roles.view',
            title: 'Peranan dihapus secara massal',
            module: 'Peranan',
            body: "{$deletable->count()} peranan",
            url: '/roles',
            level: 'warning',
        );

        return back()->with(
            'success',
            "{$deletable->count()} peranan dihapus".($skipped ? ", {$skipped} dilewati." : '.')
        );
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->name === RoleName::SuperAdmin->value) {
            return back()->with('error', 'Peranan Super Admin tidak dapat dihapus.');
        }

        if ($role->users()->exists()) {
            return back()->with('error', 'Peranan masih dipakai pengguna dan tidak dapat dihapus.');
        }

        $name = $role->name;
        $snapshot = ActivityLog::snapshotOf($role, deleted: true);
        $role->delete();

        ActivityLog::record("Menghapus peranan {$name}", 'Peranan', 'danger', changes: $snapshot);

        Notify::toPermission(
            permission: 'roles.view',
            title: 'Peranan dihapus',
            module: 'Peranan',
            body: $name,
            url: '/roles',
            level: 'warning',
        );

        return back()->with('success', "Peranan {$name} dihapus.");
    }
}
