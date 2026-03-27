<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Services\RbacPermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

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
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $expectedUsername = (string) config('catmin.admin.username', 'admin');
        $expectedPassword = (string) config('catmin.admin.password', 'admin12345');

        $isValidUsername = hash_equals($expectedUsername, (string) $data['username']);
        $isValidPassword = hash_equals($expectedPassword, (string) $data['password']);

        if (!$isValidUsername || !$isValidPassword) {
            return back()
                ->withInput($request->only('username'))
                ->withErrors([
                    'username' => 'Identifiants invalides.',
                ]);
        }

        $request->session()->regenerate();
        $request->session()->put('catmin_admin_authenticated', true);
        $request->session()->put('catmin_admin_username', $data['username']);

        $rbacContext = RbacPermissionService::resolveContextForUsername((string) $data['username']);
        $request->session()->put('catmin_rbac_roles', $rbacContext['roles']);
        $request->session()->put('catmin_rbac_permissions', $rbacContext['permissions']);
        $request->session()->put('catmin_rbac_source', $rbacContext['source']);

        return redirect()->route('admin.index');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget([
            'catmin_admin_authenticated',
            'catmin_admin_username',
            'catmin_rbac_roles',
            'catmin_rbac_permissions',
            'catmin_rbac_source',
        ]);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
