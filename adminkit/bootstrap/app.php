<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\ActivityLog;
use App\Support\Branding;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpFoundation\Response;
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
            // Belum masuk bukan kegagalan sistem: Laravel mengalihkan ke /login.
            if ($e instanceof AuthenticationException) {
                return null;
            }

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

        // Halaman error memakai design system (Inertia) alih-alih tampilan bawaan Laravel.
        // Saat APP_DEBUG aktif, kegagalan 5xx tetap menampilkan halaman debug Laravel.
        $exceptions->respond(function (Response $response, Throwable $e, Request $request) {
            $status = $response->getStatusCode();

            // 503 memakai view mandiri resources/views/errors/503.blade.php
            // (mode pemeliharaan tidak boleh bergantung pada sesi/DB/Vite).
            if (! in_array($status, [401, 403, 404, 419, 429, 500], true)) {
                return $response;
            }

            if ($request->expectsJson() || $request->header('X-Inertia-Partial-Component')) {
                return $response;
            }

            if ($status === 500 && config('app.debug')) {
                return $response;
            }

            return Inertia::render('Error', [
                'status' => $status,
                'path' => '/'.ltrim($request->path(), '/'),
                'reference' => $status >= 500 ? mb_strtoupper(substr(md5($e->getFile().$e->getLine()), 0, 8)) : '',
                // Middleware Inertia tidak selalu berjalan (mis. 404 saat routing),
                // sehingga branding & status autentikasi dikirim eksplisit di sini.
                'branding' => Branding::values(),
                'auth' => ['user' => Auth::check() ? ['name' => Auth::user()->name] : null],
            ])
                ->toResponse($request)
                ->setStatusCode($status);
        });
    })->create();
