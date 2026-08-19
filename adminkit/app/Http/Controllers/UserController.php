<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\BulkUserRequest;
use App\Http\Requests\User\ImportUserRequest;
use App\Http\Requests\User\StoreUserRequest;
use App\Models\ActivityLog;
use App\Models\User;
use App\Support\Excel;
use App\Support\Mailer;
use App\Support\Notify;
use App\Support\Rules;
use App\Support\TableQuery;
use Illuminate\Database\Eloquent\Builder;
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
    private const SORTABLE = [
        'name', 'username', 'email', 'phone', 'role', 'office',
        'alias', 'mso_code', 'collector_code', 'last_login_at',
    ];

    private const IMPORT_HEADERS = [
        'Nama Lengkap', 'Nama Pengguna', 'Alamat Email', 'Nomor HP', 'Peranan',
        'Kantor', 'Alias', 'Kode MSO', 'Kode Kolektor', 'Kata Sandi',
    ];

    private const EXPORT_HEADERS = [
        'Nama Lengkap', 'Nama Pengguna', 'Alamat Email', 'Nomor HP', 'Peranan',
        'Kantor', 'Alias', 'Kode MSO', 'Kode Kolektor', 'Status', 'Terakhir Login',
    ];

    public function index(Request $request): Response
    {
        $search = TableQuery::search($request);
        $sort = TableQuery::sort($request, self::SORTABLE, 'name');
        $dir = TableQuery::direction($request);
        $status = TableQuery::filter($request, 'status');
        $role = TableQuery::filter($request, 'role');

        $users = $this->baseQuery($search, $status, $role)
            ->orderBy($sort, $dir)
            ->paginate(TableQuery::perPage($request))
            ->withQueryString();

        return Inertia::render('Users', [
            'users' => [
                'data' => collect($users->items())->map(fn (User $u) => $this->row($u))->all(),
                'meta' => TableQuery::meta($users),
            ],
            'filters' => [
                'search' => $search,
                'sort' => $sort,
                'dir' => $dir,
                'status' => $status,
                'role' => $role,
            ],
            'roleOptions' => $this->roleOptions(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('UserForm', [
            'user' => null,
            'roleOptions' => $this->roleOptions(),
        ]);
    }

    public function edit(User $user): Response
    {
        return Inertia::render('UserForm', [
            'user' => $this->row($user),
            'roleOptions' => $this->roleOptions(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = User::create([
            ...collect($data)->except('role', 'password')->all(),
            'password' => Hash::make($data['password']),
        ]);
        $user->setRoleName($data['role']);

        ActivityLog::record(
            "Menambah pengguna {$user->name}",
            'Pengguna',
            'success',
            $user,
            ActivityLog::snapshotOf($user),
        );

        Mailer::welcome($user, $data['password']);

        Notify::toPermission(
            permission: 'users.view',
            title: 'Pengguna baru terdaftar',
            module: 'Pengguna',
            body: "{$user->name} · peranan {$data['role']}",
            url: '/users',
            level: 'success',
        );

        return to_route('users.index')->with('success', "Pengguna {$user->name} ditambahkan.");
    }

    public function update(StoreUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        $roleBefore = $user->role;
        $before = $user->getOriginal();

        $user->fill(collect($data)->except('role', 'password')->all());
        if (filled($data['password'] ?? null)) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();
        $changes = ActivityLog::diffOf($user, $before);
        $user->setRoleName($data['role']);

        if ($roleBefore !== $data['role']) {
            $changes['role'] = ['old' => $roleBefore, 'new' => $data['role']];
        }

        ActivityLog::record("Memperbarui pengguna {$user->name}", 'Pengguna', 'info', $user, $changes);

        return to_route('users.index')->with('success', "Pengguna {$user->name} diperbarui.");
    }

    /** Kirim ulang email sambutan ke pengguna tertentu. */
    public function sendWelcomeEmail(User $user): RedirectResponse
    {
        if (! $user->email) {
            return back()->with('error', "Pengguna {$user->name} belum memiliki alamat email.");
        }

        return Mailer::welcome($user)
            ? back()->with('success', "Email sambutan dikirim ke {$user->email}.")
            : back()->with('error', 'Email sambutan gagal dikirim. Periksa audit trail untuk detailnya.');
    }

    /** Berkas Excel contoh untuk impor pengguna. */
    public function importTemplate(): StreamedResponse
    {
        $role = Role::orderBy('name')->value('name') ?? 'Super Admin';

        return Excel::download(
            'template-impor-pengguna.xlsx',
            self::IMPORT_HEADERS,
            [
                ['Budi Santoso', 'budisantoso', 'budi@example.com', '081234567890', $role, 'Kantor Pusat', 'BDS', 'M001', 'K01', 'password'],
                ['Siti Aminah', 'sitiaminah', 'siti@example.com', '081234567891', $role, 'Kantor Kas', '', '', '', ''],
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
                'office' => ($cols[5] ?? '') ?: null,
                'alias' => mb_strtoupper($cols[6] ?? '') ?: null,
                'mso_code' => mb_strtoupper($cols[7] ?? '') ?: null,
                'collector_code' => mb_strtoupper($cols[8] ?? '') ?: null,
                'password' => ($cols[9] ?? '') ?: Str::password(12),
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
                'office' => Rules::text(100),
                'alias' => Rules::code(3, 'alias'),
                'mso_code' => Rules::code(4, 'mso_code'),
                'collector_code' => Rules::code(3, 'collector_code'),
                'password' => Rules::password(),
            ]);

            if ($validator->fails()) {
                $skipped++;

                continue;
            }

            $user = User::create([
                ...collect($row)->except('role', 'password')->all(),
                'password' => Hash::make($row['password']),
            ]);
            $user->setRoleName($row['role']);
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
        $rows = $this->baseQuery(
            TableQuery::search($request),
            TableQuery::filter($request, 'status'),
            TableQuery::filter($request, 'role'),
        )
            ->orderBy('name')
            ->cursor()
            ->map(fn (User $u) => [
                $u->name,
                $u->username,
                $u->email,
                $u->phone,
                $u->role,
                $u->office,
                $u->alias,
                $u->mso_code,
                $u->collector_code,
                $u->trashed() ? 'Terarsip' : 'Aktif',
                $u->last_login_at?->format('Y-m-d H:i'),
            ]);

        ActivityLog::record('Mengekspor daftar pengguna (Excel)', 'Pengguna', 'info');

        return Excel::download(Excel::filename('pengguna'), self::EXPORT_HEADERS, $rows);
    }

    /** Aksi massal: arsipkan, pulihkan, atau hapus permanen baris terpilih. */
    public function bulk(BulkUserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Akun sendiri selalu dilewati agar tidak mengunci diri sendiri.
        $users = User::withTrashed()
            ->whereIn('id', $data['ids'])
            ->whereKeyNot($request->user()->id)
            ->get();
        $skipped = count($data['ids']) - $users->count();

        if ($users->isEmpty()) {
            return back()->with('error', 'Tidak ada pengguna yang dapat diproses.');
        }

        $names = $users->pluck('name')->implode(', ');
        $count = $users->count();
        $context = ['pengguna' => $names, 'dilewati' => $skipped];

        if ($data['action'] === 'restore') {
            $users->each->restore();

            ActivityLog::record("Memulihkan {$count} pengguna secara massal", 'Pengguna', 'success', context: $context);

            return back()->with('success', "{$count} pengguna dipulihkan.");
        }

        if ($data['action'] === 'force-delete') {
            // Hanya pengguna terarsip yang boleh dihapus permanen.
            $purgeable = $users->filter->trashed();

            if ($purgeable->isEmpty()) {
                return back()->with('error', 'Arsipkan pengguna terlebih dahulu sebelum menghapus permanen.');
            }

            $count = $purgeable->count();
            $purgeable->each->forceDelete();

            ActivityLog::record("Menghapus permanen {$count} pengguna", 'Pengguna', 'danger', context: [
                'pengguna' => $purgeable->pluck('name')->implode(', '),
                'dilewati' => count($data['ids']) - $count,
            ]);

            Notify::toPermission(
                permission: 'users.view',
                title: 'Pengguna dihapus permanen',
                module: 'Pengguna',
                body: "{$count} pengguna",
                url: '/users',
                level: 'danger',
            );

            return back()->with('success', "{$count} pengguna dihapus permanen.");
        }

        $users->each->delete();

        ActivityLog::record("Mengarsipkan {$count} pengguna secara massal", 'Pengguna', 'warning', context: $context);

        Notify::toPermission(
            permission: 'users.view',
            title: 'Pengguna diarsipkan',
            module: 'Pengguna',
            body: "{$count} pengguna",
            url: '/users',
            level: 'warning',
        );

        return back()->with('success', "{$count} pengguna diarsipkan.");
    }

    /** Arsipkan (soft delete): pengguna tidak dapat masuk namun datanya tersimpan. */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'Anda tidak dapat mengarsipkan akun sendiri.');
        }

        $user->delete();

        ActivityLog::record(
            "Mengarsipkan pengguna {$user->name}",
            'Pengguna',
            'warning',
            changes: ActivityLog::snapshotOf($user, deleted: true),
        );

        Notify::toPermission(
            permission: 'users.view',
            title: 'Pengguna diarsipkan',
            module: 'Pengguna',
            body: $user->name,
            url: '/users',
            level: 'warning',
        );

        return back()->with('success', "Pengguna {$user->name} diarsipkan.");
    }

    public function restore(User $user): RedirectResponse
    {
        $user->restore();

        ActivityLog::record("Memulihkan pengguna {$user->name}", 'Pengguna', 'success', $user);

        return back()->with('success', "Pengguna {$user->name} dipulihkan.");
    }

    /** Hapus permanen: hanya untuk pengguna yang sudah terarsip. */
    public function forceDestroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'Anda tidak dapat menghapus akun sendiri.');
        }

        if (! $user->trashed()) {
            return back()->with('error', 'Arsipkan pengguna terlebih dahulu sebelum menghapus permanen.');
        }

        $name = $user->name;
        $snapshot = ActivityLog::snapshotOf($user, deleted: true);
        $user->forceDelete();

        ActivityLog::record("Menghapus permanen pengguna {$name}", 'Pengguna', 'danger', changes: $snapshot);

        return back()->with('success', "Pengguna {$name} dihapus permanen.");
    }

    /** Filter dasar dipakai daftar maupun ekspor. status: aktif | terarsip | semua. */
    private function baseQuery(string $search, string $status, string $role): Builder
    {
        return User::query()
            ->when($status === 'terarsip', fn ($q) => $q->onlyTrashed())
            ->when($status === 'semua', fn ($q) => $q->withTrashed())
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$search}%")
                ->orWhere('username', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('office', 'like', "%{$search}%")
                ->orWhere('alias', 'like', "%{$search}%")
                ->orWhere('mso_code', 'like', "%{$search}%")
                ->orWhere('collector_code', 'like', "%{$search}%")
            ))
            ->when($role !== '', fn ($q) => $q->where('role', $role));
    }

    private function row(User $u): array
    {
        return [
            'id' => $u->id,
            'name' => $u->name,
            'username' => $u->username,
            'email' => $u->email,
            'phone' => $u->phone,
            'role' => $u->role,
            'office' => $u->office,
            'alias' => $u->alias,
            'mso_code' => $u->mso_code,
            'collector_code' => $u->collector_code,
            'archived' => $u->trashed(),
            'status_label' => $u->trashed() ? 'Terarsip' : 'Aktif',
            'last_login_at' => $u->last_login_at?->timezone(config('app.timezone'))
                ->translatedFormat('d M Y, H.i') ?? '—',
        ];
    }

    /** @return array<int, array{value: string, label: string}> */
    private function roleOptions(): array
    {
        return Role::orderBy('name')->pluck('name')
            ->map(fn ($n) => ['value' => $n, 'label' => $n])->all();
    }
}
