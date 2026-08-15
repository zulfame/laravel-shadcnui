<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AppearanceController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Support\DemoData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Rute Web
|--------------------------------------------------------------------------
| Autentikasi memakai session guard bawaan Laravel; otorisasi memakai izin
| spatie/laravel-permission (middleware `permission:`), sehingga akses ditolak
| di BACKEND, bukan hanya disembunyikan di antarmuka.
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:20,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/', fn () => Inertia::render('Dashboard', [
        'kpis' => DemoData::kpis(),
        'recentUsers' => DemoData::recentUsers(),
        'activities' => DemoData::activities(),
        'trend' => DemoData::trend(),
        'byModule' => DemoData::byModule(),
        'storage' => DemoData::storage(),
    ]))->middleware('permission:dashboard.view')->name('dashboard');

    Route::middleware('permission:profile.view')->group(function () {
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])
            ->name('notifications.read-all');
        Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])
            ->name('notifications.read');

        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
        Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar');
        Route::delete('/profile/avatar', [ProfileController::class, 'destroyAvatar'])->name('profile.avatar.destroy');
    });

    Route::middleware('permission:users.view')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/export', [UserController::class, 'export'])->name('users.export');
    });

    Route::middleware('permission:users.manage')->group(function () {
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::post('/users/bulk', [UserController::class, 'bulk'])->name('users.bulk');
        Route::post('/users/import', [UserController::class, 'import'])->name('users.import');
        Route::get('/users/import/template', [UserController::class, 'importTemplate'])
            ->name('users.import.template');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });

    Route::middleware('permission:permissions.view')->group(function () {
        Route::get('/permissions', [PermissionController::class, 'index'])->name('permissions.index');
        Route::get('/permissions/export', [PermissionController::class, 'export'])->name('permissions.export');
    });

    Route::middleware('permission:permissions.manage')->group(function () {
        Route::post('/permissions', [PermissionController::class, 'store'])->name('permissions.store');
        Route::post('/permissions/generate', [PermissionController::class, 'generate'])->name('permissions.generate');
        Route::post('/permissions/bulk-destroy', [PermissionController::class, 'bulkDestroy'])
            ->name('permissions.bulk-destroy');
        Route::put('/permissions/{permission}', [PermissionController::class, 'update'])->name('permissions.update');
        Route::delete('/permissions/{permission}', [PermissionController::class, 'destroy'])
            ->name('permissions.destroy');
    });

    Route::middleware('permission:roles.view')->group(function () {
        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
        Route::get('/roles/{role}', [RoleController::class, 'show'])->name('roles.show');
    });

    Route::middleware('permission:roles.manage')->group(function () {
        Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
        Route::post('/roles/import', [RoleController::class, 'import'])->name('roles.import');
        Route::put('/roles/entity-order', [RoleController::class, 'saveEntityOrder'])
            ->name('roles.entity-order');
        Route::get('/roles/import/template', [RoleController::class, 'importTemplate'])
            ->name('roles.import.template');
        Route::post('/roles/bulk-destroy', [RoleController::class, 'bulkDestroy'])->name('roles.bulk-destroy');
        Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
        Route::put('/roles/{role}/permissions', [RoleController::class, 'syncPermissions'])
            ->name('roles.permissions');
        Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
    });

    Route::middleware('permission:activity.view')->group(function () {
        Route::get('/audit-trail', [ActivityLogController::class, 'index'])->name('audit.index');
        Route::get('/audit-trail/export', [ActivityLogController::class, 'export'])->name('audit.export');
        Route::get('/audit-trail/{log}', [ActivityLogController::class, 'show'])->name('audit.show');
    });

    Route::delete('/audit-trail', [ActivityLogController::class, 'destroyRange'])
        ->middleware('permission:activity.manage')->name('audit.destroy');

    Route::get('/appearance', [AppearanceController::class, 'edit'])
        ->middleware('permission:appearance.view')->name('appearance');

    Route::middleware('permission:appearance.manage')->group(function () {
        Route::put('/appearance/identity', [AppearanceController::class, 'updateIdentity'])->name('appearance.identity');
        Route::put('/appearance/seo', [AppearanceController::class, 'updateSeo'])->name('appearance.seo');
        Route::put('/appearance/contact', [AppearanceController::class, 'updateContact'])->name('appearance.contact');
        Route::post('/appearance/asset/{key}', [AppearanceController::class, 'uploadAsset'])->name('appearance.asset');
        Route::delete('/appearance/asset/{key}', [AppearanceController::class, 'destroyAsset'])->name('appearance.asset.destroy');
    });

});

// Alamat tak dikenal: 404 dirender lewat grup web agar sesi & prop Inertia ikut tersedia.
Route::fallback(function (Request $request) {
    if ($request->expectsJson()) {
        return response()->json(['message' => 'Not Found.'], 404);
    }

    return Inertia::render('Error', [
        'status' => 404,
        'path' => '/'.ltrim($request->path(), '/'),
    ])->toResponse($request)->setStatusCode(404);
});
