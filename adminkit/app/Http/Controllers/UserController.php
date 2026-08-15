<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\BulkUserRequest;
use App\Http\Requests\User\ImportUserRequest;
use App\Http\Requests\User\StoreUserRequest;
use App\Models\ActivityLog;
use App\Models\User;
use App\Support\Excel;
use App\Support\Notify;
use App\Support\Rules;
use App\Support\TableQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserController extends Controller
{
    private const SORTABLE = ['name', 'username', 'email', 'phone', 'role', 'is_active'];

    private const IMPORT_HEADERS = ['Nama Lengkap', 'Nama Pengguna', 'Alamat Email', 'Nomor HP', 'Peranan', 'Kata Sandi'];

    public function index(Request $request): Response
    {
        $search = TableQuery::search($request);
        $sort = TableQuery::sort($request, self::SORTABLE, 'name');
        $dir = TableQuery::direction($request);
        $status = TableQuery::filter($request, 'status');
        $role = TableQuery::filter($request, 'role');

        $users = User::query()
            ->with('roles:id,name')
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$search}%")
                ->orWhere('username', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
            ))
            ->when($status !== '', fn ($q) => $q->where('is_active', $status === 'aktif'))
            ->when($role !== '', fn ($q) => $q->whereHas('roles', fn ($r) => $r->where('name', $role)))
            ->when(
                $sort === 'role',
                fn ($q) => $q->orderBy(
                    Role::select('name')
                        ->join('model_has_roles', 'model_has_roles.role_id', '=', 'roles.id')
                        ->whereColumn('model_has_roles.model_id', 'users.id')
                        ->where('model_has_roles.model_type', User::class)
                        ->limit(1),
                    $dir
                ),
                fn ($q) => $q->orderBy($sort, $dir)
            )
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
                'role' => $role,
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

        ActivityLog::record(
            "Menambah pengguna {$user->name}",
            'Pengguna',
            'success',
            $user,
            ActivityLog::snapshotOf($user) + ['role' => ['old' => null, 'new' => $data['role']]],
        );

        Notify::toPermission(
            permission: 'users.view',
            title: 'Pengguna baru terdaftar',
            module: 'Pengguna',
            body: "{$user->name} · peranan {$data['role']}",
            url: '/users',
            level: 'success',
        );

        return back()->with('success', "Pengguna {$user->name} ditambahkan.");
    }

    public function update(StoreUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        $roleBefore = $user->roles->first()?->name;
        $before = $user->getOriginal();

        $user->fill(collect($data)->except('role', 'password')->all());
        if (filled($data['password'] ?? null)) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();
        $changes = ActivityLog::diffOf($user, $before);
        $user->syncRoles([$data['role']]);

        if ($roleBefore !== $data['role']) {
            $changes['role'] = ['old' => $roleBefore, 'new' => $data['role']];
        }

        ActivityLog::record("Memperbarui pengguna {$user->name}", 'Pengguna', 'info', $user, $changes);

        return back()->with('success', "Pengguna {$user->name} diperbarui.");
    }

    /** Berkas Excel contoh untuk impor pengguna. */
    public function importTemplate(): StreamedResponse
    {
        $role = Role::orderBy('name')->value('name') ?? 'Super Admin';

        return Excel::download(
            'template-impor-pengguna.xlsx',
            self::IMPORT_HEADERS,
            [
                ['Budi Santoso', 'budisantoso', 'budi@example.com', '081234567890', $role, 'password'],
                ['Siti Aminah', 'sitiaminah', 'siti@example.com', '081234567891', $role, ''],
            ],
            'Pengguna',
        );
    }

    /**
     * Impor pengguna dari Excel dengan kolom berurutan seperti template
     * (baris judul opsional). Baris tidak valid dilewati; kata sandi kosong diisi acak.
     */
    public function import(ImportUserRequest $request): RedirectResponse
    {
        $lines = Excel::rows($request->file('file')->getRealPath());

        $roles = Role::pluck('name')->all();
        $added = 0;
        $skipped = 0;

        foreach ($lines as $cols) {
            $cols = array_map(fn ($v) => trim((string) $v), array_values($cols));
            $row = [
                'name' => ($cols[0] ?? '') ?: null,
                'username' => ($cols[1] ?? '') ?: null,
                'email' => ($cols[2] ?? '') ?: null,
                'phone' => ($cols[3] ?? '') ?: null,
                'role' => ($cols[4] ?? '') ?: null,
                'password' => ($cols[5] ?? '') ?: Str::password(12),
            ];

            if (in_array(mb_strtolower((string) $row['name']), ['name', 'nama', 'nama lengkap'], true)) {
                continue;
            }

            $validator = Validator::make($row, [
                'name' => Rules::personName(),
                'username' => Rules::username(),
                'email' => Rules::email(),
                'phone' => Rules::phone(),
                'role' => ['required', 'string', Rule::in($roles)],
                'password' => Rules::password(),
            ]);

            if ($validator->fails()) {
                $skipped++;

                continue;
            }

            $user = User::create([
                'name' => $row['name'],
                'username' => $row['username'],
                'email' => $row['email'],
                'phone' => $row['phone'],
                'password' => Hash::make($row['password']),
                'is_active' => true,
            ]);
            $user->syncRoles([$row['role']]);
            $added++;
        }

        ActivityLog::record(
            "Mengimpor {$added} pengguna",
            'Pengguna',
            'success',
            context: ['diimpor' => $added, 'dilewati' => $skipped],
        );

        if ($added > 0) {
            Notify::toPermission(
                permission: 'users.view',
                title: 'Pengguna diimpor',
                module: 'Pengguna',
                body: "{$added} pengguna",
                url: '/users',
                level: 'success',
            );
        }

        return back()->with(
            $added > 0 ? 'success' : 'error',
            "{$added} pengguna diimpor, {$skipped} dilewati."
        );
    }

    /** Unduh Excel mengikuti filter aktif. */
    public function export(Request $request): StreamedResponse
    {
        $search = TableQuery::search($request);
        $status = TableQuery::filter($request, 'status');
        $role = TableQuery::filter($request, 'role');

        $rows = User::query()
            ->with('roles')
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$search}%")
                ->orWhere('username', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")))
            ->when($status !== '', fn ($q) => $q->where('is_active', $status === 'aktif'))
            ->when($role !== '', fn ($q) => $q->whereHas('roles', fn ($r) => $r->where('name', $role)))
            ->orderBy('name')
            ->cursor()
            ->map(fn (User $u) => [
                $u->name,
                $u->username,
                $u->email,
                $u->phone,
                $u->roles->first()?->name,
                $u->is_active ? 'Aktif' : 'Nonaktif',
                $u->last_login_at?->format('Y-m-d H:i'),
            ]);

        ActivityLog::record('Mengekspor daftar pengguna (Excel)', 'Pengguna', 'info');

        return Excel::download(
            Excel::filename('pengguna'),
            ['Nama Lengkap', 'Nama Pengguna', 'Alamat Email', 'Nomor HP', 'Peranan', 'Status', 'Terakhir Login'],
            $rows,
        );
    }

    /** Aksi massal: hapus, aktifkan, atau nonaktifkan baris terpilih. */
    public function bulk(BulkUserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Akun sendiri selalu dilewati agar tidak mengunci diri sendiri.
        $users = User::whereIn('id', $data['ids'])->whereKeyNot($request->user()->id)->get();
        $skipped = count($data['ids']) - $users->count();

        if ($users->isEmpty()) {
            return back()->with('error', 'Tidak ada pengguna yang dapat diproses.');
        }

        $names = $users->pluck('name')->implode(', ');

        if ($data['action'] === 'delete') {
            User::whereIn('id', $users->modelKeys())->delete();

            ActivityLog::record(
                "Menghapus {$users->count()} pengguna secara massal",
                'Pengguna',
                'danger',
                context: ['pengguna' => $names, 'dilewati' => $skipped],
            );

            Notify::toPermission(
                permission: 'users.view',
                title: 'Pengguna dihapus secara massal',
                module: 'Pengguna',
                body: "{$users->count()} pengguna",
                url: '/users',
                level: 'warning',
            );

            return back()->with('success', "{$users->count()} pengguna dihapus.");
        }

        $active = $data['action'] === 'activate';
        User::whereIn('id', $users->modelKeys())->update(['is_active' => $active]);

        ActivityLog::record(
            ($active ? 'Mengaktifkan ' : 'Menonaktifkan ')."{$users->count()} pengguna secara massal",
            'Pengguna',
            'info',
            context: ['pengguna' => $names, 'dilewati' => $skipped],
        );

        return back()->with(
            'success',
            "{$users->count()} pengguna ".($active ? 'diaktifkan.' : 'dinonaktifkan.')
        );
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'Anda tidak dapat menghapus akun sendiri.');
        }

        $name = $user->name;
        $snapshot = ActivityLog::snapshotOf($user, deleted: true);
        $user->delete();

        ActivityLog::record("Menghapus pengguna {$name}", 'Pengguna', 'danger', changes: $snapshot);

        Notify::toPermission(
            permission: 'users.view',
            title: 'Pengguna dihapus',
            module: 'Pengguna',
            body: $name,
            url: '/users',
            level: 'warning',
        );

        return back()->with('success', "Pengguna {$name} dihapus.");
    }
}
