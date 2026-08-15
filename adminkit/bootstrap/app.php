<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\ActivityLog;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Audit trail otomatis untuk akses ditolak (403) dan kegagalan sistem (500).
        // Dipasang pada render() — bukan report() — karena Laravel mengabaikan
        // seluruh turunan HttpException saat melapor.
        $exceptions->render(function (Throwable $e) {
            $status = match (true) {
                $e instanceof AuthorizationException, $e instanceof UnauthorizedException => 403,
                $e instanceof ValidationException => $e->status,
                $e instanceof HttpExceptionInterface => $e->getStatusCode(),
                default => 500,
            };

            if ($status !== 403 && $status < 500) {
                return null;
            }

            rescue(fn () => ActivityLog::record(
                $status === 403 ? 'Akses ditolak' : 'Kegagalan sistem',
                'Sistem',
                'danger',
                context: [
                    'pengecualian' => class_basename($e),
                    'pesan' => mb_substr($e->getMessage(), 0, 500),
                    'berkas' => str_replace(base_path().'/', '', $e->getFile()).':'.$e->getLine(),
                ],
                statusCode: $status,
            ), report: false);

            return null;
        });
    })->create();
