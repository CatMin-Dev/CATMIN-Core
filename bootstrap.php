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
require_once CATMIN_CORE . '/rbac-helpers.php';
require_once CATMIN_CORE . '/events-bus.php';
require_once CATMIN_CORE . '/error-dispatcher.php';
require_once CATMIN_CORE . '/failsafe/FailsafeManager.php';

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
$detectedEnv = $detector->detect($envManager);

define('CATMIN_ENV', $detectedEnv);
define('CATMIN_IS_DOCKER', $detector->isDocker());

$loader = new Core\config\RuntimeConfigLoader(Core\config\Config::repository(), $envManager);
$loader->load(CATMIN_CONFIG, CATMIN_STORAGE . '/config/runtime.json');
Core\versioning\VersionHistory::syncCurrentVersion();
Core\failsafe\FailsafeManager::register();

// Load module permissions into admin_permissions table during admin area requests
if (CATMIN_AREA === 'admin') {
    try {
        $permLoader = new Core\PermissionsLoader();
        $permLoader->loadFromModules();
    } catch (\Throwable) {
        // Silently fail - permissions loading shouldn't break the framework
    }
}
