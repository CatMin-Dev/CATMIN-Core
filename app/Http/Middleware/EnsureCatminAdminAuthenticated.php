<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Logger\Services\SystemLogService;
use Symfony\Component\HttpFoundation\Response;

final class EnsureCatminAdminAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->session()->get('catmin_admin_authenticated', false)) {
            return redirect()->route('admin.login');
        }

        $response = $next($request);

        /** @var SystemLogService $logger */
        $logger = app(SystemLogService::class);
        $logger->logAdminAction($request, $response->getStatusCode());

        return $response;
    }
}
