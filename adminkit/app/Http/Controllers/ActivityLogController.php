<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Support\TableQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivityLogController extends Controller
{
    public function index(Request $request): Response
    {
        $search = TableQuery::search($request);
        $module = TableQuery::filter($request, 'module');

        $logs = ActivityLog::query()
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('action', 'like', "%{$search}%")
                ->orWhere('actor_name', 'like', "%{$search}%")
                ->orWhere('ip', 'like', "%{$search}%")
            ))
            ->when($module !== '', fn ($q) => $q->where('module', $module))
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
            'filters' => ['search' => $search, 'module' => $module],
            'moduleOptions' => ActivityLog::query()
                ->select('module')->distinct()->orderBy('module')->pluck('module')
                ->map(fn ($m) => ['value' => $m, 'label' => $m])
                ->prepend(['value' => 'all', 'label' => 'Semua modul'])
                ->values()->all(),
        ]);
    }
}
