<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\RbacPermissionService;
use Closure;
use Illuminate\Http\Request;
use Modules\Logger\Services\SystemLogService;
use Symfony\Component\HttpFoundation\Response;

final class EnsureCatminPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (!RbacPermissionService::allows($request, $permission)) {
            try {
                app(SystemLogService::class)->logAudit(
                    'security.permission.denied',
                    'Acces refuse par RBAC',
                    [
                        'permission' => $permission,
                        'route' => (string) optional($request->route())->getName(),
                        'method' => $request->method(),
                        'url' => $request->fullUrl(),
                        'ip' => $request->ip(),
                    ],
                    'warning',
                    (string) $request->session()->get('catmin_admin_username', '')
                );
            } catch (\Throwable) {
                // Never break auth flow because audit logging failed.
            }

            abort(403, 'Permission insuffisante: ' . $permission);
        }

        return $next($request);
    }
}
