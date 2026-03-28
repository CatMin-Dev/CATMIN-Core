<?php

use App\Http\Middleware\EnsureCatminAdminAuthenticated;
use App\Http\Middleware\EnsureCatminApiToken;
use App\Http\Middleware\EnsureCatminExternalApiKey;
use App\Http\Middleware\EnsureCatminFrontendAvailable;
use App\Http\Middleware\EnsureCatminPermission;
use App\Http\Middleware\LogCatminExternalApi;
use App\Http\Middleware\LogRequestPerformance;
use App\Services\Api\V2Response;
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

            RateLimiter::for('catmin-external-api', function (Request $request) {
                $identity = 'ip:' . $request->ip();
                $auth = (string) $request->header('Authorization', '');
                $token = '';

                if (str_starts_with($auth, 'Bearer ')) {
                    $token = trim(substr($auth, 7));
                } elseif ($request->header('X-Catmin-Key')) {
                    $token = (string) $request->header('X-Catmin-Key');
                }

                if ($token !== '') {
                    $identity = 'key:' . substr(hash('sha256', $token), 0, 16);
                }

                $max = (int) config('catmin.api.external.rate_limit_per_minute', 120);

                return Limit::perMinute($max)->by($identity);
            });

            ModuleLoader::registerRoutes(app('router'));
            AddonLoader::registerRoutes(app('router'));
            CatminHookLoader::load();
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(LogRequestPerformance::class);

        $middleware->alias([
            'catmin.admin' => EnsureCatminAdminAuthenticated::class,
            'catmin.permission' => EnsureCatminPermission::class,
            'catmin.api-token' => EnsureCatminApiToken::class,
            'catmin.frontend.available' => EnsureCatminFrontendAvailable::class,
            'catmin.external-api-key' => EnsureCatminExternalApiKey::class,
            'catmin.external-api-log' => LogCatminExternalApi::class,
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
            if ($request->is('api/v2/*')) {
                return V2Response::error('unauthenticated', 'Authentication required.', 401);
            }
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Non authentifie.'], 401);
            }
            return response()->view('admin.pages.errors.401', [], 401);
        });

        // 403 — Authorization
        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, Request $request) {
            if ($request->is('api/v2/*')) {
                return V2Response::error('forbidden', 'Access denied.', 403);
            }
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Acces refuse.'], 403);
            }
            return response()->view('admin.pages.errors.403', [], 403);
        });

        // 422 — Validation errors for API v2
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, Request $request) {
            if (!$request->is('api/v2/*')) {
                return null;
            }

            return V2Response::error('validation_error', 'Validation failed.', 422, [
                'fields' => $e->errors(),
            ]);
        });

        // Generic HTTP errors normalization for API v2
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e, Request $request) {
            if (!$request->is('api/v2/*')) {
                return null;
            }

            $status = $e->getStatusCode();
            $message = $e->getMessage() !== '' ? $e->getMessage() : 'HTTP error';

            return V2Response::error('http_error', $message, $status);
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
            } catch (\Throwable) {
                // Prevent exception handler from throwing during bootstrap/CLI
            }
        });
    })->create();
