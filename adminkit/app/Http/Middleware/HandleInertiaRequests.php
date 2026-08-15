<?php

namespace App\Http\Middleware;

use App\Enums\RoleName;
use App\Support\Branding;
use App\Support\FileStorage;
use Illuminate\Http\Request;
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
                    'office' => $user->office,
                    'avatar' => FileStorage::url($user->avatar),
                    'has_avatar' => (bool) $user->avatar,
                    'role' => $user->getRoleNames()->first(),
                    'roles' => $user->getRoleNames(),
                    'permissions' => $user->getAllPermissions()->pluck('name'),
                    'is_admin' => $user->hasRole(RoleName::SuperAdmin->value),
                ] : null,
            ],
            'branding' => Branding::values(),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ]);
    }
}
