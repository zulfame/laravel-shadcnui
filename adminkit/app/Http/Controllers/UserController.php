<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\StoreUserRequest;
use App\Models\ActivityLog;
use App\Models\User;
use App\Support\TableQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    private const SORTABLE = ['name', 'username', 'email', 'office', 'last_login_at'];

    public function index(Request $request): Response
    {
        $search = TableQuery::search($request);
        $sort = TableQuery::sort($request, self::SORTABLE, 'name');
        $dir = TableQuery::direction($request);
        $status = TableQuery::filter($request, 'status');

        $users = User::query()
            ->with('roles:id,name')
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$search}%")
                ->orWhere('username', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('office', 'like', "%{$search}%")
            ))
            ->when($status !== '', fn ($q) => $q->where('is_active', $status === 'aktif'))
            ->orderBy($sort, $dir)
            ->paginate(TableQuery::perPage($request))
            ->withQueryString();

        return Inertia::render('Users', [
            'users' => [
                'data' => collect($users->items())->map(fn (User $u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'username' => $u->username,
                    'email' => $u->email,
                    'phone' => $u->phone,
                    'office' => $u->office,
                    'role' => $u->roles->first()?->name,
                    'is_active' => $u->is_active,
                    'status_label' => $u->is_active ? 'Aktif' : 'Nonaktif',
                    'status_chip' => $u->is_active ? '--st-done' : '--st-cancelled',
                    'last_login_at' => $u->last_login_at?->timezone(config('app.timezone'))
                        ->translatedFormat('d M Y, H.i') ?? '—',
                ])->all(),
                'meta' => TableQuery::meta($users),
            ],
            'filters' => [
                'search' => $search,
                'sort' => $sort,
                'dir' => $dir,
                'status' => $status,
            ],
            'roleOptions' => Role::orderBy('name')->pluck('name')
                ->map(fn ($n) => ['value' => $n, 'label' => $n])->all(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = User::create([
            ...collect($data)->except('role', 'password')->all(),
            'password' => Hash::make($data['password']),
        ]);
        $user->syncRoles([$data['role']]);

        ActivityLog::record("Menambah pengguna {$user->name}", 'Pengguna', 'success', $user);

        return back()->with('success', "Pengguna {$user->name} ditambahkan.");
    }

    public function update(StoreUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        $user->fill(collect($data)->except('role', 'password')->all());
        if (filled($data['password'] ?? null)) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();
        $user->syncRoles([$data['role']]);

        ActivityLog::record("Memperbarui pengguna {$user->name}", 'Pengguna', 'info', $user);

        return back()->with('success', "Pengguna {$user->name} diperbarui.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'Anda tidak dapat menghapus akun sendiri.');
        }

        $name = $user->name;
        $user->delete();

        ActivityLog::record("Menghapus pengguna {$name}", 'Pengguna', 'danger');

        return back()->with('success', "Pengguna {$name} dihapus.");
    }
}
