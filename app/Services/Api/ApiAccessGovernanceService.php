<?php

declare(strict_types=1);

namespace App\Services\Api;

use App\Models\ApiKey;
use App\Services\CatminEventBus;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Modules\Logger\Services\SystemLogService;

final class ApiAccessGovernanceService
{
    public function extractToken(Request $request): string
    {
        $header = (string) $request->header('Authorization', '');

        if (str_starts_with($header, 'Bearer ')) {
            return trim(substr($header, 7));
        }

        return (string) $request->header('X-Catmin-Key', '');
    }

    /**
     * @return array<int, string>
     */
    public function requiredScopes(string $scope): array
    {
        $required = trim($scope);
        if ($required === '') {
            return [];
        }

        $profiles = (array) config('catmin.api.external.scope_profiles', []);

        return collect([$required, ...Arr::wrap($profiles[$required] ?? [])])
            ->map(fn (mixed $item): string => trim((string) $item))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param array<int, string> $tokenScopes
     */
    public function hasScope(array $tokenScopes, string $requiredScope): bool
    {
        $expanded = $this->expandScopes($tokenScopes);

        foreach ($this->requiredScopes($requiredScope) as $candidate) {
            if ($this->matchesScope($expanded, $candidate)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<int, string> $scopes
     * @return array<int, string>
     */
    public function expandScopes(array $scopes): array
    {
        $profiles = (array) config('catmin.api.external.scope_profiles', []);
        $expanded = [];

        foreach ($scopes as $scope) {
            $scope = trim((string) $scope);
            if ($scope === '') {
                continue;
            }

            $expanded[] = $scope;

            foreach (Arr::wrap($profiles[$scope] ?? []) as $derived) {
                $expanded[] = trim((string) $derived);
            }
        }

        return collect($expanded)->filter()->unique()->values()->all();
    }

    public function identity(Request $request): string
    {
        $token = $this->extractToken($request);

        if ($token !== '') {
            return 'token:' . substr(hash('sha256', $token), 0, 24);
        }

        return 'ip:' . (string) $request->ip();
    }

    public function rateLimitConfig(string $profile): array
    {
        $profiles = (array) config('catmin.api.external.rate_limits', []);
        $resolved = (array) ($profiles[$profile] ?? $profiles['default'] ?? []);

        return [
            'limit' => max(1, (int) ($resolved['limit'] ?? 60)),
            'decay_seconds' => max(1, (int) ($resolved['decay_seconds'] ?? 60)),
            'abuse_block_seconds' => max(60, (int) ($resolved['abuse_block_seconds'] ?? 600)),
        ];
    }

    public function isBlocked(Request $request): ?int
    {
        foreach ($this->abuseKeys($request) as $key) {
            $blockedUntil = Cache::get($key . ':blocked_until');

            if (is_int($blockedUntil) && $blockedUntil > now()->timestamp) {
                return max(1, $blockedUntil - now()->timestamp);
            }
        }

        return null;
    }

    public function recordInvalidCredential(Request $request): void
    {
        $this->recordAbuse($request, 'invalid_credentials', (int) config('catmin.api.external.abuse.invalid_credentials_threshold', 5));
    }

    public function recordScopeDenied(Request $request): void
    {
        $this->recordAbuse($request, 'scope_denied', (int) config('catmin.api.external.abuse.scope_denied_threshold', 8));
    }

    public function recordRateLimitHit(Request $request, string $profile): void
    {
        $this->recordAbuse($request, 'rate_limited.' . $profile, (int) config('catmin.api.external.abuse.rate_limit_hit_threshold', 10));

        CatminEventBus::dispatch(CatminEventBus::SECURITY_RATE_LIMIT_HIT, [
            'guard' => 'external-api',
            'profile' => $profile,
            'ip' => $request->ip(),
            'identity' => $this->identity($request),
            'path' => (string) $request->path(),
        ]);
    }

    public function touchApiKey(ApiKey $apiKey, Request $request): void
    {
        $apiKey->forceFill([
            'last_used_at' => now(),
            'last_used_ip' => (string) $request->ip(),
            'usage_count' => (int) ($apiKey->usage_count ?? 0) + 1,
        ])->save();
    }

    public function logApiSecurity(string $action, string $message, Request $request, array $context = [], string $severity = 'warning'): void
    {
        try {
            app(SystemLogService::class)->logAudit(
                'api.external.' . $action,
                $message,
                array_merge([
                    'path' => (string) $request->path(),
                    'method' => (string) $request->method(),
                    'ip' => (string) $request->ip(),
                    'identity' => $this->identity($request),
                ], $context),
                $severity,
                'external-api'
            );
        } catch (\Throwable) {
            // Keep API flow resilient if logging fails.
        }
    }

    /**
     * @return array<int, string>
     */
    private function abuseKeys(Request $request): array
    {
        $ip = 'abuse:ip:' . (string) $request->ip();
        $keys = [$ip];
        $token = $this->extractToken($request);

        if ($token !== '') {
            $keys[] = 'abuse:token:' . substr(hash('sha256', $token), 0, 24);
        }

        return $keys;
    }

    private function recordAbuse(Request $request, string $bucket, int $threshold): void
    {
        $threshold = max(1, $threshold);
        $blockSeconds = max(60, (int) config('catmin.api.external.abuse.block_seconds', 600));

        foreach ($this->abuseKeys($request) as $key) {
            $counterKey = $key . ':' . $bucket;
            $hits = Cache::add($counterKey, 0, now()->addSeconds($blockSeconds)) ? 0 : (int) Cache::get($counterKey, 0);
            $hits++;
            Cache::put($counterKey, $hits, now()->addSeconds($blockSeconds));

            if ($hits >= $threshold) {
                Cache::put($key . ':blocked_until', now()->timestamp + $blockSeconds, now()->addSeconds($blockSeconds));
            }
        }
    }

    /**
     * @param array<int, string> $expandedScopes
     */
    private function matchesScope(array $expandedScopes, string $requiredScope): bool
    {
        if (in_array('*', $expandedScopes, true) || in_array($requiredScope, $expandedScopes, true)) {
            return true;
        }

        foreach ($expandedScopes as $scope) {
            if (str_ends_with($scope, '.*')) {
                $prefix = substr($scope, 0, -2);
                if ($prefix !== '' && str_starts_with($requiredScope, $prefix . '.')) {
                    return true;
                }
            }
        }

        return false;
    }
}
