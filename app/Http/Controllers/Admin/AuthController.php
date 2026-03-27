<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

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

        $expectedUsername = (string) config('catmin.admin.username', 'admin');
        $expectedPassword = (string) config('catmin.admin.password', 'admin12345');

        $isValidUsername = hash_equals($expectedUsername, (string) $data['username']);
        $isValidPassword = hash_equals($expectedPassword, (string) $data['password']);

        if (!$isValidUsername || !$isValidPassword) {
            // Log failed attempt — sans révéler lequel des deux champs est incorrect (217)
            try {
                /** @var SystemLogService $logger */
                $logger = app(SystemLogService::class);
                $logger->logAudit(
                    'auth.login.failed',
                    'Tentative de connexion echouee',
                    ['ip' => $request->ip()],
                    'warning',
                    (string) $data['username']
                );
            } catch (\Throwable) {}

            // Message générique — ne révèle pas si c'est username ou password (217)
            return back()
                ->withInput($request->only('username'))
                ->withErrors([
                    'username' => 'Identifiants incorrects.',
                ]);
        }

        // Clear rate limiter on successful login (214)
        RateLimiter::clear('catmin-login|' . $request->ip());

        $request->session()->regenerate();

        // 212 — Si 2FA activée, on ne marque pas encore l'auth comme complète
        $twoFactorEnabled = (bool) config('catmin.two_factor.enabled', false);
        $twoFactorSecret  = (string) config('catmin.two_factor.secret', '');

        if ($twoFactorEnabled && $twoFactorSecret !== '') {
            $request->session()->put('catmin_2fa_pending', true);
            $request->session()->put('catmin_2fa_pending_username', $data['username']);

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
        $request->session()->put('catmin_admin_username', $data['username']);
        // Record absolute session start time for timeout enforcement (213)
        $request->session()->put('catmin_admin_login_at', now()->timestamp);

        $rbacContext = RbacPermissionService::resolveContextForUsername((string) $data['username']);
        $request->session()->put('catmin_rbac_roles', $rbacContext['roles']);
        $request->session()->put('catmin_rbac_permissions', $rbacContext['permissions']);
        $request->session()->put('catmin_rbac_source', $rbacContext['source']);

        try {
            /** @var SystemLogService $logger */
            $logger = app(SystemLogService::class);
            $logger->logAudit(
                'auth.login',
                'Connexion admin reussie',
                [
                    'rbac_source' => $rbacContext['source'],
                    'roles'      => $rbacContext['roles'],
                    'ip'         => $request->ip(),
                ],
                'info',
                (string) $data['username']
            );
        } catch (\Throwable) {
            // Never break login flow due to audit logging failure.
        }

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
