<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Services\AdminAuthService;
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

        // 212 — Si 2FA activée, on ne marque pas encore l'auth comme complète
        $twoFactorEnabled = (bool) config('catmin.two_factor.enabled', false);
        $twoFactorSecret  = (string) config('catmin.two_factor.secret', '');

        if ($twoFactorEnabled && $twoFactorSecret !== '') {
            $request->session()->put('catmin_2fa_pending', true);
            $request->session()->put('catmin_2fa_pending_user_id', $result['user']->id);

            // Pré-charger le contexte RBAC en session pour l'avoir après 2FA
            $rbacContext = RbacPermissionService::resolveContextForUsername((string) $data['username']);
            $request->session()->put('catmin_rbac_roles', $rbacContext['roles']);
            $request->session()->put('catmin_rbac_permissions', $rbacContext['permissions']);
            $request->session()->put('catmin_rbac_source', $rbacContext['source']);

            try {
                app(\Modules\Logger\Services\SystemLogService::class)->logAudit(
                    'auth.2fa.challenge',
                    '2FA challenge envoyé',
                    ['ip' => $request->ip()],
                    'info',
                    (string) $data['username']
                );
            } catch (\Throwable) {}

            return redirect()->route('admin.2fa.verify');
        }

        $request->session()->put('catmin_admin_authenticated', true);
        $request->session()->put('catmin_admin_user_id', $result['user']->id);
        $request->session()->put('catmin_admin_username', $result['user']->username);
        // Record absolute session start time for timeout enforcement (213)
        $request->session()->put('catmin_admin_login_at', now()->timestamp);

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

        $request->session()->forget([
            'catmin_admin_authenticated',
            'catmin_admin_username',
            'catmin_admin_login_at',
            'catmin_rbac_roles',
            'catmin_rbac_permissions',
            'catmin_rbac_source',
            'catmin_2fa_verified',
        ]);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
