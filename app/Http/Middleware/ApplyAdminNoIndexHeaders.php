<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ApplyAdminNoIndexHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $adminPath = trim((string) config('catmin.admin.path', 'admin'), '/');
        $path = trim($request->path(), '/');
        $isAdminPath = $path === $adminPath || str_starts_with($path, $adminPath . '/');

        if (!$isAdminPath) {
            return $response;
        }

        $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');

        return $response;
    }
}
