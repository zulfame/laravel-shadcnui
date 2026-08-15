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

    /** Kolom rahasia yang nilainya tidak boleh masuk audit trail. */
    public const MASKED = [
        'password', 'password_confirmation', 'current_password', 'remember_token',
        's3_secret', 's3_key',
    ];

    protected $fillable = [
        'user_id', 'actor_name', 'action', 'module', 'level', 'subject_type', 'subject_id',
        'changes', 'context', 'ip', 'method', 'url', 'status_code', 'user_agent',
    ];

    protected $casts = [
        'changes' => 'array',
        'context' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Catat satu jejak audit. Pelaku diambil dari sesi aktif; bila tidak ada
     * (mis. perintah artisan) dicatat sebagai "Sistem".
     *
     * @param  array<string, array{old: mixed, new: mixed}>  $changes
     * @param  array<string, mixed>  $context
     */
    public static function record(
        string $action,
        string $module,
        string $level = 'info',
        ?Model $subject = null,
        array $changes = [],
        array $context = [],
        ?int $statusCode = null,
    ): self {
        $user = Auth::user();
        $request = request();

        return self::create([
            'user_id' => $user?->id,
            'actor_name' => $user?->name ?? 'Sistem',
            'action' => $action,
            'module' => $module,
            'level' => $level,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'changes' => $changes ?: null,
            'context' => $context ?: null,
            'ip' => $request->ip(),
            'method' => $request->method(),
            'url' => mb_substr($request->fullUrl(), 0, 500),
            'status_code' => $statusCode,
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
        ]);
    }

    /**
     * Diff atribut model setelah disimpan: nilai lama → nilai baru.
     * `$before` WAJIB diambil sebelum `save()` (`$model->getOriginal()`), karena
     * setelah tersimpan Eloquent sudah menyinkronkan nilai aslinya.
     *
     * @param  array<string, mixed>  $before
     * @return array<string, array{old: mixed, new: mixed}>
     */
    public static function diffOf(Model $model, array $before): array
    {
        $ignored = ['updated_at', 'created_at', 'remember_token'];
        $diff = [];

        foreach ($model->getChanges() as $key => $new) {
            if (in_array($key, $ignored, true)) {
                continue;
            }

            $diff[$key] = self::maskPair($key, $before[$key] ?? null, $new);
        }

        return $diff;
    }

    /** Diff untuk model yang baru dibuat atau dihapus (satu sisi saja). */
    public static function snapshotOf(Model $model, bool $deleted = false): array
    {
        $ignored = ['updated_at', 'created_at', 'remember_token', 'id'];
        $diff = [];

        foreach ($model->getAttributes() as $key => $value) {
            if (in_array($key, $ignored, true)) {
                continue;
            }

            $diff[$key] = $deleted
                ? self::maskPair($key, $value, null)
                : self::maskPair($key, null, $value);
        }

        return $diff;
    }

    /** @return array{old: mixed, new: mixed} */
    private static function maskPair(string $key, mixed $old, mixed $new): array
    {
        $masked = in_array($key, self::MASKED, true);

        return [
            'old' => $masked ? ($old === null ? null : '••••••') : $old,
            'new' => $masked ? ($new === null ? null : '••••••') : $new,
        ];
    }
}
