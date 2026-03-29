<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\ApiKey;
use App\Services\Api\V1Response;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureCatminApiV1Credential
{
    public function handle(Request $request, Closure $next, ?string $scope = null): Response
    {
        $internalToken = (string) config('catmin.api.internal_token', '');
        $providedInternalToken = (string) $request->header('X-Catmin-Token', '');

        if ($internalToken !== '' && $providedInternalToken !== '' && hash_equals($internalToken, $providedInternalToken)) {
            $request->attributes->set('catmin_api_auth_type', 'internal-token');
            $request->attributes->set('catmin_api_key_id', null);
            $request->attributes->set('catmin_api_key_name', 'internal-token');
            $request->attributes->set('catmin_api_key_scopes', ['*']);

            return $next($request);
        }

        if (!config('catmin.api.external.enabled', true)) {
            return V1Response::error('api_disabled', 'External API is disabled.', 503);
        }

        $rawToken = $this->extractToken($request);
        if ($rawToken === '') {
            return V1Response::error('unauthorized', 'Missing API credential.', 401);
        }

        $hash = hash('sha256', $rawToken);

        /** @var ApiKey|null $apiKey */
        $apiKey = ApiKey::query()
            ->where('key_hash', $hash)
            ->where('is_active', true)
            ->first();

        if (!$apiKey) {
            return V1Response::error('unauthorized', 'Invalid API key.', 401);
        }

        if ($apiKey->expires_at && $apiKey->expires_at->isPast()) {
            return V1Response::error('api_key_expired', 'API key expired.', 401);
        }

        if ($scope !== null && !$apiKey->hasScope($scope)) {
            return V1Response::error('forbidden', 'Insufficient API scope.', 403, [
                'required_scope' => $scope,
            ]);
        }

        $apiKey->forceFill([
            'last_used_at' => now(),
            'last_used_ip' => (string) $request->ip(),
        ])->save();

        $request->attributes->set('catmin_api_auth_type', 'api-key');
        $request->attributes->set('catmin_api_key_id', $apiKey->id);
        $request->attributes->set('catmin_api_key_name', (string) $apiKey->name);
        $request->attributes->set('catmin_api_key_scopes', (array) ($apiKey->scopes ?? []));

        return $next($request);
    }

    private function extractToken(Request $request): string
    {
        $header = (string) $request->header('Authorization', '');
        if (str_starts_with($header, 'Bearer ')) {
            return trim(substr($header, 7));
        }

        return (string) $request->header('X-Catmin-Key', '');
    }
}
