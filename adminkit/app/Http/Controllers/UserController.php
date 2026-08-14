<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\StoreUserRequest;
use App\Models\User;
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
        $search = trim((string) $request->string('search'));
        $sort = in_array($request->string('sort')->value(), self::SORTABLE, true)
            ? $request->string('sort')->value()
            : 'name';
        $dir = $request->string('dir')->value() === 'desc' ? 'desc' : 'asc';
        $perPage = min(max((int) $request->integer('per_page', 10), 10), 50);

        $users = User::query()
            ->with('roles:id,name')
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$search}%")
                ->orWhere('username', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('office', 'like', "%{$search}%")
            ))
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->string('status')->value() === 'aktif'))
            ->orderBy($sort, $dir)
            ->paginate($perPage)
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
                'meta' => [
                    'page' => $users->currentPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                    'last_page' => $users->lastPage(),
                ],
            ],
            'filters' => [
                'search' => $search,
                'sort' => $sort,
                'dir' => $dir,
                'status' => $request->string('status')->value(),
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

        return back()->with('success', "Pengguna {$user->name} diperbarui.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'Anda tidak dapat menghapus akun sendiri.');
        }

        $name = $user->name;
        $user->delete();

        return back()->with('success', "Pengguna {$name} dihapus.");
    }
}
