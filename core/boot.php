<?php

declare(strict_types=1);

final class CoreBoot
{
    private static bool $initialized = false;

    public static function init(): void
    {
        if (self::$initialized) {
            return;
        }

        CoreEnv::load();
        CoreConfig::load();
        self::resolveArea();
        self::redirectLegacyAdminPath();
        CoreSecurity::init();

        self::checkInstallLock();
        self::initErrorHandling();
        self::initSession();
        self::loadModules();
        self::initHooks();

        self::$initialized = true;
    }

    private static function resolveArea(): void
    {
        if (defined('CATMIN_AREA')) {
            return;
        }

        $forced = strtolower(trim((string) ($_SERVER['CATMIN_FORCE_AREA'] ?? '')));
        if ($forced === 'admin' || $forced === 'front' || $forced === 'install') {
            define('CATMIN_AREA', $forced);
            return;
        }

        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $path = (string) parse_url($uri, PHP_URL_PATH);
        $path = '/' . trim($path, '/');
        $path = $path === '//' ? '/' : $path;

        if ($path === '/install' || str_starts_with($path, '/install/')) {
            define('CATMIN_AREA', 'install');
            return;
        }

        $adminPath = '/' . trim((string) config('security.admin_path', 'admin'), '/');
        $adminPath = $adminPath === '//' ? '/admin' : $adminPath;

        define('CATMIN_AREA', $adminPath !== '/' && ($path === $adminPath || str_starts_with($path, $adminPath . '/')) ? 'admin' : 'front');
    }

    private static function redirectLegacyAdminPath(): void
    {
        if (CATMIN_AREA === 'install') {
            return;
        }

        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $path = (string) parse_url($uri, PHP_URL_PATH);
        $path = '/' . trim($path, '/');
        $path = $path === '//' ? '/' : $path;

        $adminSlug = trim((string) config('security.admin_path', 'admin'), '/');
        if ($adminSlug === '' || $adminSlug === 'admin') {
            return;
        }

        if ($path !== '/admin' && !str_starts_with($path, '/admin/')) {
            return;
        }

        $suffix = $path === '/admin' ? '' : substr($path, strlen('/admin'));
        $targetPath = '/' . $adminSlug . $suffix;
        $query = (string) parse_url($uri, PHP_URL_QUERY);
        $target = $targetPath . ($query !== '' ? ('?' . $query) : '');

        header('Location: ' . $target, true, 302);
        exit;
    }

    private static function checkInstallLock(): void
    {
        require_once CATMIN_CORE . '/install-lock-check.php';
        $isLocked = CoreInstallLockCheck::isLocked();

        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $path = (string) parse_url($uri, PHP_URL_PATH);
        $path = '/' . trim($path, '/');
        $path = $path === '//' ? '/' : $path;
        $isInstallRoute = $path === '/install' || str_starts_with($path, '/install/');

        if (!$isLocked && !$isInstallRoute && CATMIN_AREA !== 'install') {
            header('Location: /install/', true, 302);
            exit;
        }

        if ($isLocked && CATMIN_AREA === 'install') {
            $adminPath = '/' . trim((string) config('security.admin_path', 'admin'), '/');
            $adminPath = $adminPath === '//' ? '/admin' : $adminPath;
            header('Location: ' . $adminPath . '/login', true, 302);
            exit;
        }
    }

    private static function initErrorHandling(): void
    {
        require_once CATMIN_CORE . '/error-dispatcher.php';
        set_exception_handler(static function (Throwable $throwable): void {
            Core\logs\Logger::error('Unhandled exception', [
                'file' => $throwable->getFile(),
                'line' => $throwable->getLine(),
                'message' => substr($throwable->getMessage(), 0, 180),
            ]);

            (new CoreErrorDispatcher())->outputForFatal(500);
        });

        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            Core\logs\Logger::error('Runtime error', [
                'severity' => $severity,
                'file' => $file,
                'line' => $line,
                'message' => substr($message, 0, 180),
            ]);

            return false;
        });
    }

    private static function initSession(): void
    {
        if (session_status() !== PHP_SESSION_NONE) {
            return;
        }

        $https = strtolower((string) ($_SERVER['HTTPS'] ?? ''));
        $forwardedProto = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
        $secure = $https === 'on' || $https === '1' || $forwardedProto === 'https' || (string) ($_SERVER['SERVER_PORT'] ?? '') === '443';
        session_set_cookie_params([
            'lifetime' => (int) config('security.session_lifetime', 7200),
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private static function loadModules(): void
    {
        if (!defined('CATMIN_LOADED_MODULES')) {
            define('CATMIN_LOADED_MODULES', CoreLoader::loadModules());
        }
    }

    private static function initHooks(): void
    {
        if (!defined('CATMIN_HOOKS_READY')) {
            define('CATMIN_HOOKS_READY', true);
        }
    }
}
