<?php

namespace App\Http\Controllers;

use App\Enums\RoleName;
use App\Http\Requests\Role\ImportRoleRequest;
use App\Http\Requests\Role\StoreRoleRequest;
use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;
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
            ],
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $role = Role::create(['name' => $data['name'], 'guard_name' => 'web']);

        ActivityLog::record("Menambah peranan {$role->name}", 'Peranan', 'success', $role);

        return back()->with('success', "Peranan {$role->name} ditambahkan.");
    }

    public function update(StoreRoleRequest $request, Role $role): RedirectResponse
    {
        if ($role->name === RoleName::SuperAdmin->value) {
            return back()->with('error', 'Peranan Super Admin tidak dapat diubah.');
        }

        $data = $request->validated();
        $role->update(['name' => $data['name']]);

        ActivityLog::record("Memperbarui peranan {$role->name}", 'Peranan', 'info', $role);

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

        ActivityLog::record("Mengimpor {$added} peranan", 'Peranan', 'success');

        return back()->with(
            $added > 0 ? 'success' : 'error',
            "{$added} peranan diimpor, {$skipped} dilewati."
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
        $role->delete();

        ActivityLog::record("Menghapus peranan {$name}", 'Peranan', 'danger');

        return back()->with('success', "Peranan {$name} dihapus.");
    }
}
