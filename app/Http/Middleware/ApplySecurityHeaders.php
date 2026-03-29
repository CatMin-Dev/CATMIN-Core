<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ApplySecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        if (!(bool) config('catmin.security.headers.enabled', true)) {
            return $response;
        }

        $this->applyBaseHeaders($response, $request);

        if ($this->isSensitivePath($request->path())) {
            $this->applyNoStoreHeaders($response);
        }

        return $response;
    }

    private function applyBaseHeaders(Response $response, Request $request): void
    {
        $csp = trim((string) config('catmin.security.headers.csp', ''));
        if ($csp !== '') {
            $response->headers->set('Content-Security-Policy', $csp);
        }

        $frameOptions = trim((string) config('catmin.security.headers.frame_options', 'DENY'));
        if ($frameOptions !== '') {
            $response->headers->set('X-Frame-Options', $frameOptions);
        }

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', (string) config('catmin.security.headers.referrer_policy', 'strict-origin-when-cross-origin'));
        $response->headers->set('Permissions-Policy', (string) config('catmin.security.headers.permissions_policy', 'camera=(), geolocation=(), microphone=(), payment=(), usb=()'));

        $hstsEnabled = (bool) config('catmin.security.headers.hsts.enabled', true);
        $isProduction = app()->environment('production');
        if (!$hstsEnabled || !$isProduction || !$request->isSecure()) {
            return;
        }

        $maxAge = max(0, (int) config('catmin.security.headers.hsts.max_age', 31536000));
        $parts = ['max-age=' . $maxAge];

        if ((bool) config('catmin.security.headers.hsts.include_subdomains', true)) {
            $parts[] = 'includeSubDomains';
        }

        if ((bool) config('catmin.security.headers.hsts.preload', false)) {
            $parts[] = 'preload';
        }

        $response->headers->set('Strict-Transport-Security', implode('; ', $parts));
    }

    private function isSensitivePath(string $path): bool
    {
        $normalized = trim($path, '/');
        $configured = (array) config('catmin.security.headers.sensitive_paths', []);

        foreach ($configured as $prefix) {
            $prefix = trim((string) $prefix, '/');
            if ($prefix === '') {
                continue;
            }

            if ($normalized === $prefix || str_starts_with($normalized, $prefix . '/')) {
                return true;
            }
        }

        return false;
    }

    private function applyNoStoreHeaders(Response $response): void
    {
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');
    }
}
