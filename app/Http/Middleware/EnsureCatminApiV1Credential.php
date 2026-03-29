<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\ApiKey;
use App\Services\Api\ApiAccessGovernanceService;
use App\Services\Api\V1Response;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureCatminApiV1Credential
{
    public function handle(Request $request, Closure $next, ?string $scope = null): Response
    {
        $governance = app(ApiAccessGovernanceService::class);
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

        $blockedFor = $governance->isBlocked($request);
        if ($blockedFor !== null) {
            return V1Response::error('rate_limited', 'Too many requests.', 429, [], [
                'retry_after_seconds' => $blockedFor,
            ]);
        }

        $rawToken = $governance->extractToken($request);
        if ($rawToken === '') {
            $governance->recordInvalidCredential($request);
            return V1Response::error('unauthorized', 'Missing API credential.', 401);
        }

        $hash = hash('sha256', $rawToken);

        /** @var ApiKey|null $apiKey */
        $apiKey = ApiKey::query()
            ->where('key_hash', $hash)
            ->where('is_active', true)
            ->whereNull('revoked_at')
            ->first();

        if (!$apiKey) {
            $governance->recordInvalidCredential($request);
            return V1Response::error('unauthorized', 'Invalid API key.', 401);
        }

        if ($apiKey->expires_at && $apiKey->expires_at->isPast()) {
            $governance->recordInvalidCredential($request);
            return V1Response::error('api_key_expired', 'API key expired.', 401);
        }

        if ($scope !== null && !$governance->hasScope((array) ($apiKey->scopes ?? []), $scope)) {
            $governance->recordScopeDenied($request);
            return V1Response::error('forbidden', 'Insufficient API scope.', 403, [
                'required_scope' => $scope,
            ]);
        }

        $governance->touchApiKey($apiKey, $request);

        $request->attributes->set('catmin_api_auth_type', 'api-key');
        $request->attributes->set('catmin_api_key_id', $apiKey->id);
        $request->attributes->set('catmin_api_key_name', (string) $apiKey->name);
        $request->attributes->set('catmin_api_key_scopes', (array) ($apiKey->scopes ?? []));
        $request->attributes->set('catmin_api_key_model', $apiKey);

        return $next($request);
    }
}
