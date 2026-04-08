<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/core/env.php';
require_once dirname(__DIR__) . '/core/config.php';
require_once dirname(__DIR__) . '/core/security.php';
require_once dirname(__DIR__) . '/core/loader.php';
require_once dirname(__DIR__) . '/core/boot.php';
require_once dirname(__DIR__) . '/core/router.php';

CoreBoot::init();
Router::dispatch();
