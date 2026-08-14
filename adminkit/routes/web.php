<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\ProfileController;
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
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    });

    Route::get('/users', [UserController::class, 'index'])
        ->middleware('permission:users.view')->name('users.index');

    Route::middleware('permission:users.manage')->group(function () {
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });
});
