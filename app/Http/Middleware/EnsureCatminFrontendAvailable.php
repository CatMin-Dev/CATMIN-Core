<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\SettingService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureCatminFrontendAvailable
{
    public function handle(Request $request, Closure $next): Response
    {
        // Do not interfere with Laravel native maintenance mode handling.
        if (app()->isDownForMaintenance()) {
            return $next($request);
        }

        $enabled = filter_var(SettingService::get('system.maintenance_mode', false), FILTER_VALIDATE_BOOL);

        if (!$enabled) {
            return $next($request);
        }

        return response()->view('frontend.maintenance', [
            'siteName' => (string) SettingService::get('site.name', 'CATMIN'),
        ], 503);
    }
}
