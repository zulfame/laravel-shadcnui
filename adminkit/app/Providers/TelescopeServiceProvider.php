<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    /**
     * Laravel Sentinel memblokir /telescope dengan 401 ketika `APP_ENV=local`
     * diakses lewat reverse proxy publik (pod preview). Perlindungan nyata di
     * sini adalah sesi login + gate email, jadi grup middleware Telescope
     * didaftarkan ulang tanpa SentinelMiddleware.
     */
    public function boot(): void
    {
        parent::boot();

        if (config('telescope.enabled')) {
            Route::middlewareGroup('telescope', config('telescope.middleware'));
        }
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Telescope::night();

        $this->hideSensitiveRequestDetails();

        $isLocal = $this->app->environment('local');

        Telescope::filter(function (IncomingEntry $entry) use ($isLocal) {
            return $isLocal ||
                   $entry->isReportableException() ||
                   $entry->isFailedRequest() ||
                   $entry->isFailedJob() ||
                   $entry->isScheduledTask() ||
                   $entry->hasMonitoredTag();
        });
    }

    /**
     * Prevent sensitive request details from being logged by Telescope.
     */
    protected function hideSensitiveRequestDetails(): void
    {
        if ($this->app->environment('local')) {
            return;
        }

        Telescope::hideRequestParameters(['_token']);

        Telescope::hideRequestHeaders([
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
        ]);
    }

    /**
     * Register the Telescope gate.
     *
     * Hanya email pada `TELESCOPE_ALLOWED_EMAILS` yang boleh membuka Telescope.
     */
    protected function gate(): void
    {
        Gate::define('viewTelescope', function (?User $user) {
            return $user !== null && in_array($user->email, config('telescope.allowed_emails'), true);
        });
    }

    /**
     * Otorisasi ditegakkan di SEMUA environment (bawaan Telescope melewati
     * pemeriksaan saat `local`), sehingga /telescope tetap butuh sesi login
     * dengan email yang diizinkan.
     */
    protected function authorization(): void
    {
        $this->gate();

        Telescope::auth(fn ($request) => Gate::check('viewTelescope', [$request->user()]));
    }
}
