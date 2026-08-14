<?php

use App\Support\DemoData;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Rute Web
|--------------------------------------------------------------------------
| FASE STATIS: halaman dirender langsung dengan data contoh dari
| App\Support\DemoData agar tampilan dapat ditinjau lebih dulu. Autentikasi &
| basis data dipasang setelah desain disetujui. Menu ditambahkan bertahap —
| fase ini hanya Dashboard.
*/

Route::get('/login', fn () => Inertia::render('auth/Login'))->name('login');
Route::post('/login', fn () => redirect('/'));
Route::post('/logout', fn () => redirect('/login'));

Route::get('/', fn () => Inertia::render('Dashboard', [
    'kpis' => DemoData::kpis(),
    'recentUsers' => DemoData::recentUsers(),
    'activities' => DemoData::activities(),
    'trend' => DemoData::trend(),
    'byModule' => DemoData::byModule(),
    'storage' => DemoData::storage(),
]))->name('dashboard');

Route::get('/profile', fn () => Inertia::render('Profile'))->name('profile');
