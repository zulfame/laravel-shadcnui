<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /** Tandai satu notifikasi sebagai dibaca (hanya milik pengguna aktif). */
    public function markRead(Request $request, Notification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        $notification->read_at ?? $notification->update(['read_at' => now()]);

        return back();
    }

    /** Tandai seluruh notifikasi pengguna aktif sebagai dibaca. */
    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->notifications()->whereNull('read_at')->update(['read_at' => now()]);

        return back();
    }
}
