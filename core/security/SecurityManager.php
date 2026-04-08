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
require_once CATMIN_CORE . '/error-dispatcher.php';

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
            $secureCookie = Config::get('security.session_cookie_secure', null);
            if (!is_bool($secureCookie)) {
                $secureCookie = $this->isHttpsRequest();
            }

            session_set_cookie_params([
                'lifetime' => (int) Config::get('security.session_lifetime', 7200),
                'path' => '/',
                'secure' => $secureCookie,
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
        $sensitive = $this->area === 'admin' || $this->area === 'install';
        $isHttps = $this->isHttpsRequest();

        return $this->headers->apply($response, $this->csp->build($directives), $noindex, $isHttps, $sensitive);
    }

    public function enforceIpWhitelist(): ?Response
    {
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');

        if ($this->ipWhitelist->isAllowed($ip)) {
            return null;
        }

        $this->audit->log('security.ip.denied', 'warning', [
            'area' => $this->area,
            'ip' => $ip,
            'path' => $this->request->path(),
        ]);

        if ($this->area === 'admin') {
            return (new \CoreErrorDispatcher())->adminAccessDenied([
                'message' => 'Adresse IP non autorisée pour la zone admin.',
            ]);
        }

        return (new \CoreErrorDispatcher())->response(403, [
            'message' => 'Adresse IP non autorisée.',
        ]);
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

                    return (new \CoreErrorDispatcher())->response(419);
                }

                if ((bool) Config::get('security.csrf_rotate_on_validation', true)) {
                    $this->csrf->regenerate();
                }
            }

            $result = $next($request);

            return $result instanceof Response ? $result : Response::html((string) $result);
        };
    }

    public function installAvailabilityMiddleware(): callable
    {
        return function (Request $request, callable $next): Response {
            $lockFile = CATMIN_STORAGE . '/install/installed.lock';
            if (is_file($lockFile)) {
                $this->audit->log('security.install.access_after_lock', 'warning', [
                    'ip' => (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
                    'path' => $request->path(),
                ]);
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

    public function maintenanceModeMiddleware(): callable
    {
        return function (Request $request, callable $next): Response {
            if (!$this->isMaintenanceEnabled()) {
                $result = $next($request);
                return $result instanceof Response ? $result : Response::html((string) $result);
            }

            $allowAdmin = $this->isMaintenanceAdminBypassEnabled();
            if ($this->area === 'admin' && $allowAdmin) {
                $result = $next($request);
                return $result instanceof Response ? $result : Response::html((string) $result);
            }

            return (new \CoreErrorDispatcher())->maintenance([
                'message' => 'CATMIN est en maintenance. Merci de réessayer plus tard.',
            ]);
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

    private function isHttpsRequest(): bool
    {
        $https = strtolower((string) ($_SERVER['HTTPS'] ?? ''));
        if ($https === 'on' || $https === '1') {
            return true;
        }

        $forwardedProto = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
        if ($forwardedProto === 'https') {
            return true;
        }

        return (string) ($_SERVER['SERVER_PORT'] ?? '') === '443';
    }

    private function isMaintenanceEnabled(): bool
    {
        try {
            $pdo = (new ConnectionManager())->connection();
            $table = (string) Config::get('database.prefixes.core', 'core_') . 'settings';
            $stmt = $pdo->prepare('SELECT setting_value FROM ' . $table . ' WHERE category = :category AND setting_key = :setting_key LIMIT 1');
            $stmt->execute(['category' => 'maintenance', 'setting_key' => 'enabled']);
            $value = (string) ($stmt->fetchColumn() ?: '0');
            return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
        } catch (\Throwable) {
            return false;
        }
    }

    private function isMaintenanceAdminBypassEnabled(): bool
    {
        try {
            $pdo = (new ConnectionManager())->connection();
            $table = (string) Config::get('database.prefixes.core', 'core_') . 'settings';
            $stmt = $pdo->prepare('SELECT setting_value FROM ' . $table . ' WHERE category = :category AND setting_key = :setting_key LIMIT 1');
            $stmt->execute(['category' => 'maintenance', 'setting_key' => 'allow_admin']);
            $value = (string) ($stmt->fetchColumn() ?: '1');
            return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
        } catch (\Throwable) {
            return true;
        }
    }
}
