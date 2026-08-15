<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Support\TableQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivityLogController extends Controller
{
    public function index(Request $request): Response
    {
        $search = TableQuery::search($request);
        $module = TableQuery::filter($request, 'module');
        [$from, $to] = $this->range($request);

        $logs = $this->query($search, $module, $from, $to)
            ->latest('id')
            ->paginate(TableQuery::perPage($request))
            ->withQueryString();

        return Inertia::render('ActivityLog', [
            'logs' => [
                'data' => collect($logs->items())->map(fn (ActivityLog $log) => [
                    'id' => $log->id,
                    'created_at' => $log->created_at->timezone(config('app.timezone'))->translatedFormat('d M Y, H.i'),
                    'actor' => $log->actor_name,
                    'action' => $log->action,
                    'module' => $log->module,
                    'ip' => $log->ip ?? '—',
                    'level_label' => ActivityLog::LEVEL_LABELS[$log->level] ?? $log->level,
                    'level_chip' => ActivityLog::LEVEL_CHIPS[$log->level] ?? '--st-draft',
                ])->all(),
                'meta' => TableQuery::meta($logs),
            ],
            'filters' => [
                'search' => $search,
                'module' => $module,
                'date_from' => $from,
                'date_to' => $to,
            ],
            'moduleOptions' => ActivityLog::query()
                ->select('module')->distinct()->orderBy('module')->pluck('module')
                ->map(fn ($m) => ['value' => $m, 'label' => $m])
                ->prepend(['value' => 'all', 'label' => 'Semua modul'])
                ->values()->all(),
        ]);
    }

    /**
     * Hapus log pada rentang tanggal tertentu (inklusif).
     */
    public function destroyRange(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
        ], [], ['date_from' => 'tanggal awal', 'date_to' => 'tanggal akhir']);

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

    private function query(string $search, string $module, string $from, string $to)
    {
        return ActivityLog::query()
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('action', 'like', "%{$search}%")
                ->orWhere('actor_name', 'like', "%{$search}%")
                ->orWhere('ip', 'like', "%{$search}%")
            ))
            ->when($module !== '', fn ($q) => $q->where('module', $module))
            ->when($from !== '', fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to !== '', fn ($q) => $q->whereDate('created_at', '<=', $to));
    }
}
