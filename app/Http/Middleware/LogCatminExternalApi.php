<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Logger\Services\SystemLogService;
use Symfony\Component\HttpFoundation\Response;

final class LogCatminExternalApi
{
    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = microtime(true);

        /** @var Response $response */
        $response = $next($request);

        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

        try {
            app(SystemLogService::class)->logAudit(
                'api.external.access',
                'External API access',
                [
                    'path' => (string) $request->path(),
                    'method' => (string) $request->method(),
                    'status_code' => $response->getStatusCode(),
                    'duration_ms' => $durationMs,
                    'ip' => (string) $request->ip(),
                    'auth_type' => $request->attributes->get('catmin_api_auth_type'),
                    'api_key_id' => $request->attributes->get('catmin_api_key_id'),
                    'api_key_name' => $request->attributes->get('catmin_api_key_name'),
                    'api_scopes' => (array) $request->attributes->get('catmin_api_key_scopes', []),
                    'api_scope' => $request->attributes->get('catmin_api_scope'),
                    'rate_limit_profile' => $response->headers->get('X-Catmin-RateLimit-Profile'),
                ],
                $response->getStatusCode() >= 400 ? 'warning' : 'info',
                'external-api'
            );
        } catch (\Throwable) {
            // Keep API flow resilient if logging fails.
        }

        return $response;
    }
}
