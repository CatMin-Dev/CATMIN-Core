<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\ApiKey;
use App\Services\Api\ApiAccessGovernanceService;
use App\Services\Api\V2Response;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureCatminApiScope
{
    public function handle(Request $request, Closure $next, string $scope): Response
    {
        /** @var ApiKey|null $apiKey */
        $apiKey = $request->attributes->get('catmin_api_key_model');

        if (!$apiKey) {
            return V2Response::error('unauthorized', 'Authentication required.', 401);
        }

        $governance = app(ApiAccessGovernanceService::class);
        $tokenScopes = (array) ($apiKey->scopes ?? []);

        if (!$governance->hasScope($tokenScopes, $scope)) {
            $governance->recordScopeDenied($request);
            $governance->logApiSecurity('scope.denied', 'External API scope denied', $request, [
                'required_scope' => $scope,
                'api_key_id' => $apiKey->id,
                'api_key_name' => (string) $apiKey->name,
                'token_scopes' => $tokenScopes,
            ]);

            return V2Response::error('forbidden', 'Insufficient API scope.', 403, [
                'required_scope' => $scope,
            ]);
        }

        $request->attributes->set('catmin_api_scope', $scope);

        return $next($request);
    }
}
