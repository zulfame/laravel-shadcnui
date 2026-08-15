<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AppearanceController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\StorageController;
use App\Http\Controllers\UserController;
use App\Support\DemoData;
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

    Route::get('/users', [UserController::class, 'index'])
        ->middleware('permission:users.view')->name('users.index');

    Route::middleware('permission:users.manage')->group(function () {
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::post('/users/bulk', [UserController::class, 'bulk'])->name('users.bulk');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });

    Route::middleware('permission:roles.view')->group(function () {
        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
        Route::get('/roles/{role}', [RoleController::class, 'show'])->name('roles.show');
    });

    Route::middleware('permission:roles.manage')->group(function () {
        Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
        Route::post('/roles/import', [RoleController::class, 'import'])->name('roles.import');
        Route::post('/roles/bulk-destroy', [RoleController::class, 'bulkDestroy'])->name('roles.bulk-destroy');
        Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
        Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
    });

    Route::middleware('permission:activity.view')->group(function () {
        Route::get('/audit-trail', [ActivityLogController::class, 'index'])->name('audit.index');
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

    Route::get('/storage-settings', [StorageController::class, 'edit'])
        ->middleware('permission:storage.view')->name('storage');

    Route::middleware('permission:storage.manage')->group(function () {
        Route::put('/storage-settings', [StorageController::class, 'update'])->name('storage.update');
        Route::post('/storage-settings/test', [StorageController::class, 'test'])->name('storage.test');
    });
});
