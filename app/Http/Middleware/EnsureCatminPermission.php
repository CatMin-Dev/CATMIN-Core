<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\RbacPermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureCatminPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (!RbacPermissionService::allows($request, $permission)) {
            abort(403, 'Permission insuffisante: ' . $permission);
        }

        return $next($request);
    }
}
