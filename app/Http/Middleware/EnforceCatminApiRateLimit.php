<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Api\ApiAccessGovernanceService;
use App\Services\Api\V2Response;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

final class EnforceCatminApiRateLimit
{
    public function handle(Request $request, Closure $next, string $profile = 'default'): Response
    {
        $governance = app(ApiAccessGovernanceService::class);
        $blockedFor = $governance->isBlocked($request);

        if ($blockedFor !== null) {
            return $this->rateLimitedResponse($request, $profile, $blockedFor, 0, $blockedFor);
        }

        $config = $governance->rateLimitConfig($profile);
        $limit = (int) ($config['limit'] ?? 60);
        $decaySeconds = (int) ($config['decay_seconds'] ?? 60);
        $identity = 'catmin-api:' . $profile . ':' . $governance->identity($request);

        if (RateLimiter::tooManyAttempts($identity, $limit)) {
            $retryAfter = max(1, RateLimiter::availableIn($identity));
            $governance->recordRateLimitHit($request, $profile);
            $governance->logApiSecurity('rate-limited', 'External API rate limit exceeded', $request, [
                'profile' => $profile,
                'retry_after_seconds' => $retryAfter,
                'limit' => $limit,
            ]);

            return $this->rateLimitedResponse($request, $profile, $retryAfter, 0, $retryAfter);
        }

        RateLimiter::hit($identity, $decaySeconds);
        $remaining = max(0, $limit - RateLimiter::attempts($identity));

        /** @var Response $response */
        $response = $next($request);
        $response->headers->set('X-RateLimit-Limit', (string) $limit);
        $response->headers->set('X-RateLimit-Remaining', (string) $remaining);
        $response->headers->set('X-Catmin-RateLimit-Profile', $profile);

        return $response;
    }

    private function rateLimitedResponse(Request $request, string $profile, int $retryAfter, int $remaining, int $remainingSeconds): Response
    {
        $response = V2Response::error('rate_limited', 'Too many requests.', 429, [], [
            'retry_after_seconds' => $retryAfter,
            'rate_limit_profile' => $profile,
        ]);

        $response->headers->set('Retry-After', (string) $retryAfter);
        $response->headers->set('X-RateLimit-Remaining', (string) $remaining);
        $response->headers->set('X-Catmin-Blocked-For', (string) $remainingSeconds);

        return $response;
    }
}
