<?php

use App\Http\Middleware\EnsureCatminAdminAuthenticated;
use App\Services\ModuleLoader;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Modules\Logger\Services\SystemLogService;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            ModuleLoader::registerRoutes(app('router'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'catmin.admin' => EnsureCatminAdminAuthenticated::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->report(function (\Throwable $throwable): void {
            /** @var SystemLogService $logger */
            $logger = app(SystemLogService::class);
            $logger->logError($throwable, [
                'url' => request()?->fullUrl(),
                'method' => request()?->method(),
                'ip' => request()?->ip(),
            ]);
        });
    })->create();
