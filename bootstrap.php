<?php

declare(strict_types=1);

define('CATMIN_START', microtime(true));
define('CATMIN_ROOT', __DIR__);
define('CATMIN_CORE', CATMIN_ROOT . '/core');
define('CATMIN_ADMIN', CATMIN_ROOT . '/admin');
define('CATMIN_FRONT', CATMIN_ROOT . '/front');
define('CATMIN_INSTALL', CATMIN_ROOT . '/install');
define('CATMIN_MODULES', CATMIN_ROOT . '/modules');
define('CATMIN_PUBLIC', CATMIN_ROOT . '/public');
define('CATMIN_STORAGE', CATMIN_ROOT . '/storage');
define('CATMIN_CONFIG', CATMIN_ROOT . '/config');

if (!defined('CATMIN_AREA')) {
    define('CATMIN_AREA', 'front');
}

require_once CATMIN_CORE . '/support/helpers.php';

spl_autoload_register(static function (string $class): void {
    $prefixMap = [
        'Core\\' => CATMIN_CORE . '/',
        'Admin\\' => CATMIN_ADMIN . '/',
    ];

    foreach ($prefixMap as $prefix => $basePath) {
        if (!str_starts_with($class, $prefix)) {
            continue;
        }

        $relative = substr($class, strlen($prefix));
        $path = $basePath . str_replace('\\', '/', $relative) . '.php';

        if (is_file($path)) {
            require_once $path;
        }
    }
});

$env = getenv('APP_ENV');
if (!is_string($env) || $env === '') {
    $env = 'production';
}
define('CATMIN_ENV', $env);

Core\config\Config::loadDirectory(CATMIN_CONFIG);

set_exception_handler(static function (Throwable $throwable): void {
    Core\logs\Logger::error(
        $throwable->getMessage(),
        [
            'file' => $throwable->getFile(),
            'line' => $throwable->getLine(),
            'trace' => $throwable->getTraceAsString(),
        ]
    );

    http_response_code(500);
    echo 'Internal Server Error';
});

set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    Core\logs\Logger::error($message, [
        'severity' => $severity,
        'file' => $file,
        'line' => $line,
    ]);

    return false;
});
