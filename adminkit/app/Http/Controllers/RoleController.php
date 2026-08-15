<?php

namespace App\Http\Controllers;

use App\Enums\RoleName;
use App\Http\Requests\Role\StoreRoleRequest;
use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
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
