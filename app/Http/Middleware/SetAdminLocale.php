<?php

namespace App\Http\Middleware;

use App\Models\AdminUser;
use App\Services\LocaleService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SetAdminLocale — applies the resolved admin locale on every authenticated admin request.
 *
 * Must run after session middleware so the session is available.
 * Should run after catmin.admin auth middleware so the admin user ID is in session.
 */
class SetAdminLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $adminUserId = (int) $request->session()->get('catmin_admin_user_id', 0);

            $adminUser = null;
            if ($adminUserId > 0) {
                $adminUser = AdminUser::query()->find($adminUserId);
            }

            $locale = LocaleService::resolve($adminUser instanceof AdminUser ? $adminUser : null);
            LocaleService::apply($locale);
        } catch (\Throwable) {
            // Never break a request over locale resolution
            LocaleService::apply(LocaleService::DEFAULT_LOCALE);
        }

        return $next($request);
    }
}
