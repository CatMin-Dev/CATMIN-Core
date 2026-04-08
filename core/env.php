<?php

declare(strict_types=1);

if (!defined('CATMIN_START')) {
    define('CATMIN_START', microtime(true));
}

if (!defined('CATMIN_ROOT')) {
    define('CATMIN_ROOT', dirname(__DIR__));
    define('CATMIN_CORE', CATMIN_ROOT . '/core');
    define('CATMIN_ADMIN', CATMIN_ROOT . '/admin');
    define('CATMIN_FRONT', CATMIN_ROOT . '/front');
    define('CATMIN_INSTALL', CATMIN_ROOT . '/install');
    define('CATMIN_MODULES', CATMIN_ROOT . '/modules');
    define('CATMIN_PUBLIC', CATMIN_ROOT . '/public');
    define('CATMIN_STORAGE', CATMIN_ROOT . '/storage');
    define('CATMIN_CONFIG', CATMIN_ROOT . '/config');
}

final class CoreEnv
{
    private static bool $loaded = false;

    public static function load(): void
    {
        if (self::$loaded) {
            return;
        }

        require_once CATMIN_CORE . '/support/helpers.php';

        spl_autoload_register(static function (string $class): void {
            $prefixMap = [
                'Core\\' => CATMIN_CORE . '/',
                'Admin\\' => CATMIN_ADMIN . '/',
                'Install\\' => CATMIN_INSTALL . '/',
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

        $envManager = new Core\config\EnvManager();
        $envManager->loadFile(CATMIN_ROOT . '/.env', false);

        $detector = new Core\config\EnvironmentDetector();
        if (!defined('CATMIN_ENV')) {
            define('CATMIN_ENV', $detector->detect($envManager));
        }

        if (!defined('CATMIN_IS_DOCKER')) {
            define('CATMIN_IS_DOCKER', $detector->isDocker());
        }

        self::$loaded = true;
    }
}
