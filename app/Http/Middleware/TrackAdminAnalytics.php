<?php

namespace App\Http\Middleware;

use App\Services\AnalyticsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackAdminAnalytics
{
    public function __construct(private readonly AnalyticsService $analyticsService)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $adminPath = trim((string) config('catmin.admin.path', 'admin'), '/');
        $path = trim($request->path(), '/');
        $isAdminPath = $path === $adminPath || str_starts_with($path, $adminPath . '/');

        if (!$isAdminPath) {
            return $response;
        }

        if ($request->isMethod('GET')) {
            $eventName = 'admin.module.opened';
            $action = 'opened';
        } else {
            $eventName = 'admin.action.performed';
            $action = strtolower($request->method());
        }

        $status = $response->getStatusCode() >= 400 ? 'failed' : 'success';

        $routeName = (string) ($request->route()?->getName() ?? '');
        $domain = $this->resolveDomain($routeName, $path);

        $this->analyticsService->track(
            eventName: $eventName,
            domain: $domain,
            action: $action,
            status: $status,
            context: [
                'route' => $routeName,
                'method' => strtoupper($request->method()),
            ],
            metadata: [
                'status_code' => $response->getStatusCode(),
                'is_ajax' => $request->ajax(),
            ]
        );

        return $response;
    }

    private function resolveDomain(string $routeName, string $path): string
    {
        if (str_contains($routeName, 'addons.marketplace')) {
            return 'module';
        }

        if (str_contains($routeName, 'docs.') || str_contains($path, '/docs')) {
            return 'docs';
        }

        if (str_contains($routeName, 'queue.') || str_contains($routeName, 'cron.') || str_contains($routeName, 'mailer.')) {
            return 'ops';
        }

        if (str_contains($routeName, 'content.') || str_contains($routeName, 'pages.') || str_contains($routeName, 'articles.')) {
            return 'content';
        }

        if (str_contains($routeName, 'modules.') || str_contains($routeName, 'settings.')) {
            return 'module';
        }

        return 'admin';
    }
}
