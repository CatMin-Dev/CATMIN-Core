<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class AuthController extends Controller
{
    public function showLogin()
    {
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
            return redirect(route('error.403.blade'));

        }

        $request->session()->put('catmin_admin_authenticated', true);
        $request->session()->put('catmin_admin_username', $data['username']);

        return redirect('/admin/access');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget([
            'catmin_admin_authenticated',
            'catmin_admin_username',
        ]);

        return redirect('/admin/login');
    }
}
