<?php

use App\Http\Middleware\EnsureCatminAdminAuthenticated;
use App\Services\AddonLoader;
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
            AddonLoader::registerRoutes(app('router'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'catmin.admin' => EnsureCatminAdminAuthenticated::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
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
