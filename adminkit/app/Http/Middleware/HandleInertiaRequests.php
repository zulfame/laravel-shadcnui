<?php

namespace App\Http\Middleware;

use App\Enums\RoleName;
use App\Models\Menu;
use App\Models\Notification;
use App\Support\Branding;
use App\Support\FileStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    /**
     * Data yang dibagikan ke setiap halaman Inertia.
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'avatar' => FileStorage::url($user->avatar),
                    'has_avatar' => (bool) $user->avatar,
                    'role' => $user->getRoleNames()->first(),
                    'roles' => $user->getRoleNames(),
                    'permissions' => $user->getAllPermissions()->pluck('name'),
                    'is_admin' => $user->hasRole(RoleName::SuperAdmin->value),
                ] : null,
            ],
            'notifications' => $user ? [
                // Hanya notifikasi milik pengguna yang sedang masuk.
                'unread' => fn () => $user->notifications()->whereNull('read_at')->count(),
                'items' => fn () => self::mapNotifications($user->notifications()->limit(10)->get()),
                'unread_items' => fn () => self::mapNotifications(
                    $user->notifications()->whereNull('read_at')->limit(10)->get()
                ),
            ] : ['unread' => 0, 'items' => [], 'unread_items' => []],
            'menu' => fn () => Menu::treeFor($user),
            'branding' => Branding::values(),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ]);
    }

    /** @param  Collection<int, Notification>  $notifications */
    private static function mapNotifications($notifications): array
    {
        return $notifications->map(fn ($n) => [
            'id' => $n->id,
            'title' => $n->title,
            'body' => $n->body,
            'module' => $n->module,
            'level' => $n->level,
            'url' => $n->url,
            'unread' => $n->read_at === null,
            'time' => $n->created_at->diffForHumans(),
        ])->all();
    }
}
