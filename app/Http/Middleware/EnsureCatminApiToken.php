<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Logger\Services\SystemLogService;
use Symfony\Component\HttpFoundation\Response;

final class EnsureCatminApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('catmin.api.internal_token', env('CATMIN_API_INTERNAL_TOKEN', ''));

        if ($expected === '') {
            return response()->json([
                'success' => false,
                'message' => 'Internal API token not configured.',
            ], 503);
        }

        // 215 — Token uniquement via header, jamais via query string (évite exposition dans logs serveur)
        $provided = (string) ($request->header('X-Catmin-Token') ?? '');

        if ($provided === '' || !hash_equals($expected, $provided)) {
            // Log tentative invalide avec IP (215)
            try {
                /** @var SystemLogService $logger */
                $logger = app(SystemLogService::class);
                $logger->logAudit(
                    'api.token.invalid',
                    'Tentative acces API sans token valide',
                    [
                        'ip'     => $request->ip(),
                        'path'   => $request->path(),
                        'method' => $request->method(),
                    ],
                    'warning',
                    'api'
                );
            } catch (\Throwable) {}

            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        return $next($request);
    }
}
