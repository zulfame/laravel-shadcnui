<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class ActivityLog extends Model
{
    /** Warna chip per level (token CSS, lihat resources/css/app.css). */
    public const LEVEL_CHIPS = [
        'info' => '--st-progress',
        'success' => '--st-done',
        'warning' => '--st-pending',
        'danger' => '--st-overdue',
    ];

    public const LEVEL_LABELS = [
        'info' => 'Info',
        'success' => 'Sukses',
        'warning' => 'Peringatan',
        'danger' => 'Gagal',
    ];

    protected $fillable = [
        'user_id', 'actor_name', 'action', 'module', 'level', 'subject_type', 'subject_id', 'ip',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Catat satu aktivitas. Pelaku diambil dari sesi aktif; bila tidak ada
     * (mis. perintah artisan) dicatat sebagai "Sistem".
     */
    public static function record(string $action, string $module, string $level = 'info', ?Model $subject = null): self
    {
        $user = Auth::user();

        return self::create([
            'user_id' => $user?->id,
            'actor_name' => $user?->name ?? 'Sistem',
            'action' => $action,
            'module' => $module,
            'level' => $level,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'ip' => request()->ip(),
        ]);
    }
}
