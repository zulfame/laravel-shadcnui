<?php

namespace App\Providers;

use App\Support\Branding;
use Carbon\Carbon;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Tanggal & waktu relatif mengikuti bahasa aplikasi.
        Carbon::setLocale(config('app.locale'));

        // Branding dipakai blade root untuk judul, favicon, dan meta SEO.
        View::composer('app', function ($view) {
            $view->with('branding', Branding::values());
        });

        // Halaman pemeliharaan mandiri: pakai branding bila DB masih terjangkau.
        View::composer('errors::503', function ($view) {
            $branding = rescue(fn () => Branding::values(), [], report: false);
            $down = rescue(
                fn () => json_decode((string) file_get_contents(storage_path('framework/down')), true),
                [],
                report: false
            );

            $view->with([
                'appName' => $branding['app_name'] ?? config('app.name'),
                'brandInitials' => $branding['brand_initials'] ?? null,
                'supportEmail' => $branding['support_email'] ?? null,
                'tagline' => $branding['tagline'] ?? null,
                'footerText' => $branding['footer_text'] ?? null,
                'retryAfter' => $down['retry'] ?? 60,
            ]);
        });
    }
}
