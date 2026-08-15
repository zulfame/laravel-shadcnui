<?php

namespace App\Providers;

use App\Support\Branding;
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
        // Branding dipakai blade root untuk judul, favicon, dan meta SEO.
        View::composer('app', function ($view) {
            $view->with('branding', Branding::values());
        });
    }
}
