<?php

namespace App\Http\Controllers;

use App\Enums\RoleName;
use App\Http\Requests\Role\StoreRoleRequest;
use App\Models\ActivityLog;
use App\Support\Modules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            'modules' => Modules::matrix(),
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $role = Role::create(['name' => $data['name'], 'guard_name' => 'web']);
        $role->syncPermissions($data['permissions'] ?? []);

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
        $role->syncPermissions($data['permissions'] ?? []);

        ActivityLog::record("Memperbarui peranan {$role->name}", 'Peranan', 'info', $role);

        return back()->with('success', "Peranan {$role->name} diperbarui.");
    }

    /**
     * Simpan seluruh matriks izin sekaligus (satu tombol Simpan).
     */
    public function syncMatrix(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'matrix' => ['required', 'array'],
            'matrix.*' => ['array'],
            'matrix.*.*' => ['string'],
        ]);

        DB::transaction(function () use ($validated) {
            foreach ($validated['matrix'] as $roleId => $permissions) {
                $role = Role::find($roleId);
                if (! $role || $role->name === RoleName::SuperAdmin->value) {
                    continue;
                }
                $role->syncPermissions(array_intersect($permissions, Modules::permissions()));
            }
        });

        ActivityLog::record('Memperbarui matriks hak akses', 'Peranan', 'info');

        return back()->with('success', 'Matriks hak akses disimpan.');
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
