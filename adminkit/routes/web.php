<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Support\DemoData;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Rute Web
|--------------------------------------------------------------------------
| Autentikasi memakai session guard bawaan Laravel + spatie/laravel-permission.
| Data dashboard masih contoh (App\Support\DemoData) sampai modulnya dibangun.
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
    ]))->name('dashboard');

    Route::get('/profile', fn () => Inertia::render('Profile'))->name('profile');
});
