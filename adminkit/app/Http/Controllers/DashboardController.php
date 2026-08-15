<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Notification;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Dashboard', [
            'kpis' => $this->kpis(),
            'recentUsers' => $this->recentUsers(),
            'activities' => $this->activities(),
            'trend' => $this->trend(),
            'byModule' => $this->byModule(),
            'roleDistribution' => $this->roleDistribution(),
            'storage' => $this->storage(),
        ]);
    }

    private function kpis(): array
    {
        $users = User::count();
        $active = User::where('is_active', true)->count();
        $permissions = Permission::count();
        $entities = Permission::pluck('name')
            ->map(fn (string $name) => str($name)->before('.')->value())
            ->unique()->count();
        $weekLogs = ActivityLog::where('created_at', '>=', now()->subDays(7))->count();
        $failures = ActivityLog::whereIn('level', ['danger', 'warning'])
            ->where('created_at', '>=', now()->subDays(7))->count();
        $unread = Notification::where('user_id', Auth::id())->where('is_read', false)->count();

        return [
            [
                'key' => 'users',
                'label' => 'Total Pengguna',
                'value' => number_format($users, 0, ',', '.'),
                'hint' => "{$active} aktif · ".($users - $active).' nonaktif',
            ],
            [
                'key' => 'roles',
                'label' => 'Peranan',
                'value' => (string) Role::count(),
                'hint' => $permissions.' izin terpetakan',
            ],
            [
                'key' => 'permissions',
                'label' => 'Izin',
                'value' => (string) $permissions,
                'hint' => $entities.' entitas modul',
            ],
            [
                'key' => 'activity',
                'label' => 'Aktivitas 7 Hari',
                'value' => number_format($weekLogs, 0, ',', '.'),
                'hint' => $failures.' perlu ditinjau',
            ],
            [
                'key' => 'notifications',
                'label' => 'Notifikasi',
                'value' => (string) $unread,
                'hint' => 'belum dibaca',
            ],
        ];
    }

    private function recentUsers(): array
    {
        return User::with('roles:name')->latest()->take(8)->get()
            ->map(function (User $user) {
                $filled = collect([$user->username, $user->email, $user->phone, $user->avatar])
                    ->filter()->count();

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email ?? '—',
                    'role' => $user->roles->pluck('name')->join(', ') ?: '—',
                    'completeness' => (int) round($filled / 4 * 100),
                    'status_label' => $user->is_active ? 'Aktif' : 'Nonaktif',
                    'status_chip' => $user->is_active ? '--st-done' : '--st-cancelled',
                ];
            })->all();
    }

    private function activities(): array
    {
        return ActivityLog::with('user:id,name')->latest()->take(10)->get()
            ->map(fn (ActivityLog $log) => [
                'id' => $log->id,
                'time' => $log->created_at->translatedFormat('H.i'),
                'action' => $log->action,
                'actor' => $log->user?->name ?? 'Sistem',
                'module' => $log->module,
            ])->all();
    }

    /** Pengguna baru vs aktivitas per hari, 7 hari terakhir. */
    private function trend(): array
    {
        $days = collect(range(6, 0))->map(fn (int $back) => CarbonImmutable::today()->subDays($back));

        return $days->map(fn (CarbonImmutable $day) => [
            'label' => $day->translatedFormat('D'),
            'created' => User::whereDate('created_at', $day)->count(),
            'active' => ActivityLog::whereDate('created_at', $day)->count(),
        ])->all();
    }

    private function byModule(): array
    {
        return ActivityLog::selectRaw('module as label, count(*) as count')
            ->groupBy('module')->orderByDesc('count')->take(6)->get()
            ->map(fn ($row) => ['label' => $row->label, 'count' => (int) $row->count])
            ->all();
    }

    private function roleDistribution(): array
    {
        return Role::withCount('users')->orderByDesc('users_count')->orderBy('name')->take(8)->get()
            ->map(fn (Role $role) => ['label' => $role->name, 'count' => $role->users_count])
            ->all();
    }

    /** Pemakaian nyata: berkas unggahan, basis data, dan disk. */
    private function storage(): array
    {
        $disk = Storage::disk('public');
        $uploads = collect($disk->allFiles())->sum(fn (string $file) => $disk->size($file));
        $database = @filesize(config('database.connections.sqlite.database')) ?: 0;
        $diskTotal = @disk_total_space(base_path()) ?: 1;
        $diskUsed = $diskTotal - (@disk_free_space(base_path()) ?: 0);

        return [
            $this->usage('Berkas Unggahan', $uploads, max($uploads, 50 * 1024 * 1024)),
            $this->usage('Basis Data', $database, max($database, 100 * 1024 * 1024)),
            $this->usage('Disk Server', (int) $diskUsed, (int) $diskTotal),
        ];
    }

    private function usage(string $label, int $used, int $total): array
    {
        return [
            'label' => $label,
            'used' => $this->bytes($used),
            'total' => $this->bytes($total),
            'percent' => (int) round($used / max($total, 1) * 100),
        ];
    }

    private function bytes(int $bytes): string
    {
        foreach (['B', 'KB', 'MB', 'GB', 'TB'] as $index => $unit) {
            if ($bytes < 1024 || $unit === 'TB') {
                return number_format($bytes, $index > 1 ? 1 : 0, ',', '.').' '.$unit;
            }

            $bytes /= 1024;
        }

        return '0 B';
    }
}
