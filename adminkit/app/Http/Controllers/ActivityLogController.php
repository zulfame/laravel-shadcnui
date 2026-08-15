<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActivityLog\DestroyRangeRequest;
use App\Models\ActivityLog;
use App\Support\Csv;
use App\Support\TableQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActivityLogController extends Controller
{
    private const SORTABLE = ['created_at', 'actor_name', 'action', 'module', 'level'];

    public function index(Request $request): Response
    {
        $search = TableQuery::search($request);
        [$from, $to] = $this->range($request);
        $sort = TableQuery::sort($request, self::SORTABLE, 'created_at');
        $dir = TableQuery::direction($request, 'desc');

        $logs = $this->query($search, $from, $to)
            ->orderBy($sort, $dir)
            ->paginate(TableQuery::perPage($request))
            ->withQueryString();

        return Inertia::render('AuditTrail', [
            'logs' => [
                'data' => collect($logs->items())->map(fn (ActivityLog $log) => [
                    'id' => $log->id,
                    'created_at' => $log->created_at->timezone(config('app.timezone'))->translatedFormat('d M Y, H.i'),
                    'actor' => $log->actor_name,
                    'action' => $log->action,
                    'module' => $log->module,
                    'ip' => $log->ip ?? '—',
                    'level' => $log->level,
                    'level_label' => ActivityLog::LEVEL_LABELS[$log->level] ?? $log->level,
                    'level_chip' => ActivityLog::LEVEL_CHIPS[$log->level] ?? '--st-draft',
                    'subject' => $log->subject_type
                        ? class_basename($log->subject_type).' #'.$log->subject_id
                        : '—',
                    'user_id' => $log->user_id,
                    'changes' => $log->changes,
                    'context' => $log->context,
                    'method' => $log->method,
                    'url' => $log->url,
                    'status_code' => $log->status_code,
                    'user_agent' => $log->user_agent,
                    'created_at_full' => $log->created_at->timezone(config('app.timezone'))
                        ->translatedFormat('l, d F Y H:i:s'),
                ])->all(),
                'meta' => TableQuery::meta($logs),
            ],
            'filters' => [
                'search' => $search,
                'sort' => $sort,
                'dir' => $dir,
                'date_from' => $from,
                'date_to' => $to,
            ],
        ]);
    }

    /**
     * Hapus log pada rentang tanggal tertentu (inklusif).
     */
    /** Halaman detail satu jejak audit (untuk pengembang: lengkap & mentah). */
    public function show(ActivityLog $log): Response
    {
        $log->load('user');

        return Inertia::render('AuditDetail', [
            'log' => [
                'id' => $log->id,
                'action' => $log->action,
                'module' => $log->module,
                'level' => $log->level,
                'level_label' => ActivityLog::LEVEL_LABELS[$log->level] ?? $log->level,
                'level_chip' => ActivityLog::LEVEL_CHIPS[$log->level] ?? '--st-draft',
                'actor' => $log->actor_name,
                'user_id' => $log->user_id,
                'actor_email' => $log->user?->email,
                'subject_type' => $log->subject_type,
                'subject_id' => $log->subject_id,
                'subject' => $log->subject_type
                    ? class_basename($log->subject_type).' #'.$log->subject_id
                    : null,
                'changes' => $log->changes,
                'context' => $log->context,
                'ip' => $log->ip,
                'method' => $log->method,
                'url' => $log->url,
                'status_code' => $log->status_code,
                'user_agent' => $log->user_agent,
                'created_at_iso' => $log->created_at->toIso8601String(),
                'created_at_full' => $log->created_at->timezone(config('app.timezone'))
                    ->translatedFormat('l, d F Y H:i:s'),
                'created_at_diff' => $log->created_at->diffForHumans(),
            ],
        ]);
    }

    /** Unduh CSV mengikuti filter aktif. */
    public function export(Request $request): StreamedResponse
    {
        $search = TableQuery::search($request);
        [$from, $to] = $this->range($request);

        $rows = $this->query($search, $from, $to)
            ->orderBy(TableQuery::sort($request, self::SORTABLE, 'created_at'), TableQuery::direction($request, 'desc'))
            ->cursor()
            ->map(fn (ActivityLog $log) => [
                $log->created_at->format('Y-m-d H:i:s'),
                $log->actor_name,
                $log->action,
                $log->module,
                ActivityLog::LEVEL_LABELS[$log->level] ?? $log->level,
                $log->ip,
                $log->method,
                $log->status_code,
                $log->url,
            ]);

        ActivityLog::record('Mengekspor audit trail (CSV)', 'Audit Trail', 'info');

        return Csv::stream(
            Csv::filename('audit-trail'),
            ['Waktu', 'Pelaku', 'Aksi', 'Modul', 'Level', 'Alamat IP', 'Metode', 'Kode Status', 'URL'],
            $rows,
        );
    }

    public function destroyRange(DestroyRangeRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $deleted = ActivityLog::query()
            ->whereDate('created_at', '>=', $data['date_from'])
            ->whereDate('created_at', '<=', $data['date_to'])
            ->delete();

        ActivityLog::record(
            "Menghapus {$deleted} log aktivitas ({$data['date_from']} s.d. {$data['date_to']})",
            'Log Aktivitas',
            'danger'
        );

        return back()->with('success', "{$deleted} log aktivitas dihapus.");
    }

    /** Rentang tanggal dari permintaan (format YYYY-MM-DD). */
    private function range(Request $request): array
    {
        return [
            $request->date('date_from') ? $request->string('date_from')->value() : '',
            $request->date('date_to') ? $request->string('date_to')->value() : '',
        ];
    }

    private function query(string $search, string $from, string $to)
    {
        return ActivityLog::query()
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('action', 'like', "%{$search}%")
                ->orWhere('actor_name', 'like', "%{$search}%")
                ->orWhere('ip', 'like', "%{$search}%")
            ))
            ->when($from !== '', fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to !== '', fn ($q) => $q->whereDate('created_at', '<=', $to));
    }
}
