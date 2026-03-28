<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\AdminSessionService;
use Closure;
use Illuminate\Http\Request;
use Modules\Logger\Services\SystemLogService;
use Symfony\Component\HttpFoundation\Response;

final class EnsureCatminAdminAuthenticated
{
    // Absolute session lifetime in seconds (213 — 8 heures max quelle que soit l'activité)
    private const ABSOLUTE_TIMEOUT = 8 * 60 * 60;

    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->session()->get('catmin_admin_authenticated', false)) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Non authentifie.'], 401);
            }
            return redirect()->route('admin.login');
        }

        // Absolute timeout check (213)
        $loginAt = (int) $request->session()->get('catmin_admin_login_at', 0);
        if ($loginAt > 0 && (now()->timestamp - $loginAt) > self::ABSOLUTE_TIMEOUT) {
            $request->session()->flush();
            $request->session()->regenerateToken();
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Session expiree.'], 401);
            }
            return redirect()->route('admin.login')
                ->withErrors(['username' => 'Session expiree. Reconnecte-toi.']);
        }

        $sessionService = app(AdminSessionService::class);

        $sessionId = $request->session()->getId();
        if ($sessionService->isRevoked($sessionId)) {
            $request->session()->flush();
            $request->session()->regenerateToken();

            return redirect()->route('admin.login')
                ->withErrors(['username' => 'Session revoquee. Reconnecte-toi.']);
        }

        $idleTimeoutMinutes = (int) config('catmin.admin.session_idle_timeout_minutes', 0);
        if ($idleTimeoutMinutes > 0) {
            $lastActivityAt = (int) $request->session()->get('catmin_admin_last_activity_at', 0);
            if ($lastActivityAt > 0 && (now()->timestamp - $lastActivityAt) > ($idleTimeoutMinutes * 60)) {
                $sessionService->revokeCurrent($request);
                $request->session()->flush();
                $request->session()->regenerateToken();

                return redirect()->route('admin.login')
                    ->withErrors(['username' => 'Session inactive expiree. Reconnecte-toi.']);
            }

            $request->session()->put('catmin_admin_last_activity_at', now()->timestamp);
        }

        $sessionService->touch($request);

        $response = $next($request);

        /** @var SystemLogService $logger */
        $logger = app(SystemLogService::class);
        $logger->logAdminAction($request, $response->getStatusCode());

        return $response;
    }
}

