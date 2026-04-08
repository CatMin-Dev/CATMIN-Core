<?php

declare(strict_types=1);

final class CoreConfig
{
    private static bool $loaded = false;

    public static function load(): void
    {
        if (self::$loaded) {
            return;
        }

        CoreEnv::load();

        $envManager = new Core\config\EnvManager();
        $envManager->loadFile(CATMIN_ROOT . '/.env', false);

        $loader = new Core\config\RuntimeConfigLoader(Core\config\Config::repository(), $envManager);
        $loader->load(CATMIN_CONFIG, CATMIN_STORAGE . '/config/runtime.json');

        self::$loaded = true;
    }
}
