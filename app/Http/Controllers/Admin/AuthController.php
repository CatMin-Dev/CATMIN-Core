<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Services\AdminAuthService;
use App\Services\AdminSessionService;
use App\Services\CatminEventBus;
use App\Services\RbacPermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Logger\Services\SystemLogService;

final class AuthController extends Controller
{
    public function showLogin()
    {
        if (session('catmin_admin_authenticated', false)) {
            return redirect()->route('admin.index');
        }

        return view('admin.pages.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        // Rate limit login attempts per IP
        $rateLimitKey = 'catmin-login|' . $request->ip();
        if (RateLimiter::tooManyAttempts($rateLimitKey, 10)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            CatminEventBus::dispatch(CatminEventBus::SECURITY_RATE_LIMIT_HIT, [
                'guard' => 'admin',
                'username' => (string) ($data['username'] ?? ''),
                'ip' => $request->ip(),
                'retry_after_seconds' => $seconds,
            ]);

            return back()
                ->withErrors(['username' => "Trop de tentatives. Réessayez dans {$seconds} secondes."])
                ->withInput($request->only('username'));
        }

        RateLimiter::hit($rateLimitKey, 900); // 15 minutes

        // Attempt authentication via AdminAuthService (DB-based)
        $authService = app(AdminAuthService::class);
        $result = $authService->attempt(
            (string) $data['username'],
            (string) $data['password']
        );

        if (!$result['success']) {
            CatminEventBus::dispatch(CatminEventBus::AUTH_LOGIN_FAILED, [
                'guard' => 'admin',
                'username' => (string) ($data['username'] ?? ''),
                'ip' => $request->ip(),
                'reason' => (string) ($result['error'] ?? 'invalid_credentials'),
            ]);

            // Log failed attempt — sans révéler lequel des deux champs est incorrect (217)
            try {
                /** @var SystemLogService $logger */
                $logger = app(SystemLogService::class);
                $logger->logAudit(
                    'auth.login.failed',
                    'Tentative de connexion échouée',
                    ['ip' => $request->ip()],
                    'warning',
                    (string) $data['username']
                );
            } catch (\Throwable) {}

            // Message générique — ne révèle pas si c'est username ou password (217)
            return back()
                ->withInput($request->only('username'))
                ->withErrors(['username' => $result['error'] ?? 'Identifiants invalides.']);
        }

        // Clear rate limiter on successful login (214)
        RateLimiter::clear($rateLimitKey);

        $request->session()->regenerate();

        // 327 — 2FA est per-account, plus global .env
        $user = $result['user'];
        if ((bool) ($user->two_factor_enabled ?? false) && (string) ($user->two_factor_secret ?? '') !== '') {
            $request->session()->put('catmin_2fa_pending', true);
            $request->session()->put('catmin_2fa_pending_user_id', (int) $user->id);
            $request->session()->put('catmin_2fa_pending_username', (string) $user->username);

            try {
                app(SystemLogService::class)->logAudit(
                    'auth.2fa.challenge',
                    '2FA challenge envoye',
                    ['ip' => $request->ip()],
                    'info',
                    (string) $user->username
                );
            } catch (\Throwable) {
            }

            return redirect()->route('admin.2fa.verify');
        }

        $request->session()->put('catmin_admin_authenticated', true);
        $request->session()->put('catmin_admin_user_id', $result['user']->id);
        $request->session()->put('catmin_admin_username', $result['user']->username);
        // Record absolute session start time for timeout enforcement (213)
        $request->session()->put('catmin_admin_login_at', now()->timestamp);
        $request->session()->put('catmin_admin_last_activity_at', now()->timestamp);

        $rbacContext = RbacPermissionService::resolveContextForUsername($result['user']->username);
        $request->session()->put('catmin_rbac_roles', $rbacContext['roles']);
        $request->session()->put('catmin_rbac_permissions', $rbacContext['permissions']);
        $request->session()->put('catmin_rbac_source', $rbacContext['source']);

        try {
            /** @var SystemLogService $logger */
            $logger = app(SystemLogService::class);
            $logger->logAudit(
                'auth.login',
                'Connexion admin réussie',
                [
                    'rbac_source' => $rbacContext['source'],
                    'roles'      => $rbacContext['roles'],
                    'ip'         => $request->ip(),
                ],
                'info',
                $result['user']->username
            );
        } catch (\Throwable) {
            // Never break login flow due to audit logging failure.
        }

        CatminEventBus::dispatch(CatminEventBus::AUTH_LOGIN_SUCCEEDED, [
            'guard' => 'admin',
            'user' => [
                'id' => (int) $result['user']->id,
                'username' => (string) $result['user']->username,
            ],
            'ip' => $request->ip(),
            'rbac_source' => (string) ($rbacContext['source'] ?? 'direct'),
            'roles' => (array) ($rbacContext['roles'] ?? []),
        ]);

        app(AdminSessionService::class)->registerSession($request, (int) $result['user']->id);

        return redirect()->route('admin.index');
    }

    public function logout(Request $request): RedirectResponse
    {
        $username = (string) $request->session()->get('catmin_admin_username', '');

        try {
            /** @var SystemLogService $logger */
            $logger = app(SystemLogService::class);
            $logger->logAudit('auth.logout', 'Deconnexion admin', ['ip' => $request->ip()], 'info', $username);
        } catch (\Throwable) {
            // Never break logout flow due to audit logging failure.
        }

        CatminEventBus::dispatch(CatminEventBus::AUTH_LOGOUT, [
            'guard' => 'admin',
            'username' => $username,
            'ip' => $request->ip(),
        ]);

        app(AdminSessionService::class)->revokeCurrent($request);

        $request->session()->forget([
            'catmin_admin_authenticated',
            'catmin_admin_username',
            'catmin_admin_login_at',
            'catmin_admin_last_activity_at',
            'catmin_rbac_roles',
            'catmin_rbac_permissions',
            'catmin_rbac_source',
            'catmin_2fa_verified',
            'catmin_2fa_pending',
            'catmin_2fa_pending_user_id',
            'catmin_2fa_pending_username',
            'catmin_2fa_setup_secret',
            'catmin_2fa_new_recovery_codes',
        ]);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
