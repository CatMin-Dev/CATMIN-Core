<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureCatminAdminAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->session()->get('catmin_admin_authenticated', false)) {
            return redirect()->route('admin.login');
        }

        return $next($request);
    }
}
