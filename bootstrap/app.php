<?php

use App\Http\Middleware\EnsureCatminAdminAuthenticated;
use App\Http\Middleware\ApplyAdminNoIndexHeaders;
use App\Http\Middleware\EnsureCatminApiToken;
use App\Http\Middleware\EnsureCatminFrontendAvailable;
use App\Http\Middleware\EnsureCatminPermission;
use App\Http\Middleware\ApplySecurityHeaders;
use App\Http\Middleware\LogRequestPerformance;
use App\Http\Middleware\TrackAdminAnalytics;
use App\Http\Middleware\SetAdminLocale;
use App\Services\AddonLoader;
use App\Services\CatminHookLoader;
use App\Services\ModuleLoader;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Modules\Logger\Services\AlertingService;
use Modules\Logger\Services\SystemLogService;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            RateLimiter::for('catmin-login', function (Request $request) {
                return Limit::perMinute(5)->by($request->ip())->response(function () {
                    return redirect()->route('admin.login')
                        ->withErrors(['username' => 'Trop de tentatives. Attendez 1 minute.']);
                });
            });

            RateLimiter::for('catmin-api', function (Request $request) {
                return Limit::perMinute(60)->by($request->ip());
            });

            RateLimiter::for('catmin-password-reset', function (Request $request) {
                $email = strtolower((string) $request->input('email', ''));
                return Limit::perMinute(5)->by($request->ip() . '|' . $email);
            });

            RateLimiter::for('catmin-contact', function (Request $request) {
                return Limit::perMinute(3)->by($request->ip())
                    ->response(function () {
                        return redirect()->route('frontend.contact')
                            ->withErrors(['message' => 'Trop de tentatives. Attendez quelques minutes.']);
                    });
            });

            ModuleLoader::registerRoutes(app('router'));
            AddonLoader::registerRoutes(app('router'));
            CatminHookLoader::load();
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(ApplySecurityHeaders::class);
        $middleware->append(ApplyAdminNoIndexHeaders::class);
        $middleware->append(LogRequestPerformance::class);
        $middleware->append(TrackAdminAnalytics::class);

        $middleware->alias([
            'catmin.admin' => EnsureCatminAdminAuthenticated::class,
            'catmin.permission' => EnsureCatminPermission::class,
            'catmin.api-token' => EnsureCatminApiToken::class,
            'catmin.frontend.available' => EnsureCatminFrontendAvailable::class,
            'catmin.locale' => SetAdminLocale::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // 419 — CSRF token mismatch → render admin error page
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Session expiree (CSRF).'], 419);
            }
            return response()->view('admin.pages.errors.419', [], 419);
        });

        // 401 — Unauthenticated
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Non authentifie.'], 401);
            }
            return response()->view('admin.pages.errors.401', [], 401);
        });

        // 403 — Authorization
        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Acces refuse.'], 403);
            }
            return response()->view('admin.pages.errors.403', [], 403);
        });

        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, Request $request) {
            if (!$request->expectsJson()) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e, Request $request) {
            if (!$request->expectsJson()) {
                return null;
            }

            $status = $e->getStatusCode();
            $message = $e->getMessage() !== '' ? $e->getMessage() : 'HTTP error';

            return response()->json([
                'success' => false,
                'message' => $message,
            ], $status);
        });

        $exceptions->report(function (\Throwable $throwable): void {
            try {
                /** @var SystemLogService $logger */
                $logger = app(SystemLogService::class);
                $isConsole = app()->runningInConsole();
                $logger->logError($throwable, [
                    'url'    => $isConsole ? null : request()?->fullUrl(),
                    'method' => $isConsole ? null : request()?->method(),
                    'ip'     => $isConsole ? null : request()?->ip(),
                ]);

                if (!app()->runningUnitTests()) {
                    app(AlertingService::class)->alertCriticalError(
                        $throwable->getMessage() !== '' ? $throwable->getMessage() : get_class($throwable),
                        (string) $throwable->getFile(),
                        (int) $throwable->getLine(),
                        [
                            'exception' => get_class($throwable),
                            'code' => $throwable->getCode(),
                        ]
                    );
                }
            } catch (\Throwable) {
                // Prevent exception handler from throwing during bootstrap/CLI
            }
        });
    })->create();
