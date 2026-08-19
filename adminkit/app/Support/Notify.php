<?php

namespace App\Support;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Pengiriman notifikasi bertarget: hanya pengguna yang BERWENANG atas modul
 * terkait yang menerimanya, dan pelaku aksi tidak diberi tahu tentang aksinya
 * sendiri.
 */
class Notify
{
    /** Kirim ke semua pengguna aktif (tidak terarsip) yang memiliki izin tertentu. */
    public static function toPermission(
        string $permission,
        string $title,
        string $module,
        ?string $body = null,
        ?string $url = null,
        string $level = 'info',
    ): int {
        $actor = Auth::user();

        $recipients = User::query()
            ->when($actor, fn ($q) => $q->whereKeyNot($actor->getKey()))
            ->get()
            ->filter(fn (User $user) => $user->can($permission));

        if ($recipients->isEmpty()) {
            return 0;
        }

        $now = now();

        Notification::insert($recipients->map(fn (User $user) => [
            'user_id' => $user->id,
            'title' => $title,
            'body' => $body,
            'module' => $module,
            'level' => $level,
            'url' => $url,
            'actor_id' => $actor?->id,
            'read_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all());

        return $recipients->count();
    }

    /** Kirim ke satu pengguna tertentu. */
    public static function toUser(
        User $user,
        string $title,
        string $module,
        ?string $body = null,
        ?string $url = null,
        string $level = 'info',
    ): void {
        Notification::create([
            'user_id' => $user->id,
            'title' => $title,
            'body' => $body,
            'module' => $module,
            'level' => $level,
            'url' => $url,
            'actor_id' => Auth::id(),
        ]);
    }
}
