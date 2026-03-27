<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
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

        $provided = (string) ($request->header('X-Catmin-Token') ?? $request->query('token', ''));

        if ($provided === '' || !hash_equals($expected, $provided)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        return $next($request);
    }
}
