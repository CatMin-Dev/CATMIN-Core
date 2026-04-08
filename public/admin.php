<?php

declare(strict_types=1);

$_SERVER['CATMIN_FORCE_AREA'] = 'admin';

if (!isset($_SERVER['REQUEST_URI']) || (string) $_SERVER['REQUEST_URI'] === '' || (string) $_SERVER['REQUEST_URI'] === '/admin.php') {
    $_SERVER['REQUEST_URI'] = '/admin/login';
}

require_once dirname(__DIR__) . '/core/env.php';
require_once dirname(__DIR__) . '/core/config.php';
require_once dirname(__DIR__) . '/core/security.php';
require_once dirname(__DIR__) . '/core/loader.php';
require_once dirname(__DIR__) . '/core/boot.php';
require_once dirname(__DIR__) . '/core/router.php';

CoreBoot::init();
Router::dispatch();
