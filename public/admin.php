<?php

declare(strict_types=1);

$_SERVER['CATMIN_FORCE_AREA'] = 'admin';

require_once dirname(__DIR__) . '/core/env.php';
require_once dirname(__DIR__) . '/core/config.php';
CoreEnv::load();
CoreConfig::load();

if (!isset($_SERVER['REQUEST_URI']) || (string) $_SERVER['REQUEST_URI'] === '' || (string) $_SERVER['REQUEST_URI'] === '/admin.php') {
    $adminPath = '/' . trim((string) config('security.admin_path', 'admin'), '/');
    $adminPath = $adminPath === '//' ? '/admin' : $adminPath;
    $_SERVER['REQUEST_URI'] = $adminPath . '/login';
}

require_once dirname(__DIR__) . '/core/security.php';
require_once dirname(__DIR__) . '/core/loader.php';
require_once dirname(__DIR__) . '/core/boot.php';
require_once dirname(__DIR__) . '/core/router.php';

CoreBoot::init();
Router::dispatch();
