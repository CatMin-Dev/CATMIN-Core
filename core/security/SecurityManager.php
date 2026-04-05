<?php

declare(strict_types=1);

namespace Core\security;

use Admin\controllers\AuthController;
use Core\auth\ReAuthManager;
use Core\auth\SessionManager;
use Core\config\Config;
use Core\database\ConnectionManager;
use Core\http\Request;
use Core\http\Response;

final class SecurityManager
{
    private CsrfManager $csrf;
    private HeaderManager $headers;
    private CspBuilder $csp;
    private IpWhitelistManager $ipWhitelist;
    private SecurityAuditLogger $audit;

    public function __construct(private readonly Request $request, private readonly string $area)
    {
        $this->csrf = new CsrfManager();
        $this->headers = new HeaderManager();
        $this->csp = new CspBuilder();
        $this->ipWhitelist = new IpWhitelistManager();
        $this->audit = new SecurityAuditLogger();
    }

    public function boot(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'lifetime' => (int) Config::get('security.session_lifetime', 7200),
                'path' => '/',
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }

        if (!$this->ipWhitelist->isAllowed((string) ($_SERVER['REMOTE_ADDR'] ?? ''))) {
            $this->audit->log('security.ip.blocked', 'warning', [
                'area' => $this->area,
                'ip' => (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
                'path' => $this->request->path(),
            ]);
        }
    }

    public function apply(Response $response): Response
    {
        $directives = Config::get('security.csp', $this->csp->defaultPolicy());
        if (!is_array($directives)) {
            $directives = $this->csp->defaultPolicy();
        }

        $noindex = $this->area === 'front' || $this->area === 'install' || (bool) Config::get('security.admin_noindex', true);

        return $this->headers->apply($response, $this->csp->build($directives), $noindex);
    }

    public function enforceIpWhitelist(): ?Response
    {
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');

        if ($this->ipWhitelist->isAllowed($ip)) {
            return null;
        }

        return Response::text('Access denied (IP whitelist).', 403);
    }

    public function csrfCheckMiddleware(): callable
    {
        return function (Request $request, callable $next): Response {
            if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
                $token = (string) ($request->input('_csrf', $request->header('X-CSRF-Token', '')));
                if (!$this->csrf->validate($token)) {
                    $this->audit->log('security.csrf.invalid', 'warning', [
                        'area' => $this->area,
                        'path' => $request->path(),
                        'ip' => (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
                    ]);

                    return Response::text('Invalid CSRF token.', 419);
                }
            }

            $result = $next($request);

            return $result instanceof Response ? $result : Response::html((string) $result);
        };
    }

    public function installAvailabilityMiddleware(): callable
    {
        return static function (Request $request, callable $next): Response {
            $lockFile = CATMIN_STORAGE . '/install/installed.lock';
            if (is_file($lockFile)) {
                $adminPath = '/' . trim((string) Config::get('security.admin_path', 'admin'), '/');
                return Response::html('', 302, ['Location' => $adminPath . '/login']);
            }

            $result = $next($request);

            return $result instanceof Response ? $result : Response::html((string) $result);
        };
    }

    public function noindexFrontMiddleware(): callable
    {
        return static function (Request $request, callable $next): Response {
            $result = $next($request);

            $response = $result instanceof Response ? $result : Response::html((string) $result);

            return $response->withHeader('X-Robots-Tag', 'noindex, nofollow');
        };
    }

    public function adminAuthRequiredMiddleware(): callable
    {
        return static function (Request $request, callable $next): Response {
            $controller = new AuthController();

            if (!$controller->requiresAuth()) {
                return Response::html('', 302, ['Location' => $controller->adminBasePath() . '/login']);
            }

            $result = $next($request);

            return $result instanceof Response ? $result : Response::html((string) $result);
        };
    }

    public function recentPasswordRequiredMiddleware(): callable
    {
        return static function (Request $request, callable $next): Response {
            $controller = new AuthController();
            if (!$controller->requiresRecentReauth()) {
                return Response::html('', 302, ['Location' => $controller->adminBasePath() . '/reauth']);
            }

            $result = $next($request);

            return $result instanceof Response ? $result : Response::html((string) $result);
        };
    }

    public function securityHeadersMiddleware(): callable
    {
        return function (Request $request, callable $next): Response {
            $result = $next($request);

            $response = $result instanceof Response ? $result : Response::html((string) $result);

            return $this->apply($response);
        };
    }

    public function csrf(): CsrfManager
    {
        return $this->csrf;
    }

    public function reauthManager(): ReAuthManager
    {
        $session = new SessionManager((new ConnectionManager())->connection());

        return new ReAuthManager($session);
    }
}
