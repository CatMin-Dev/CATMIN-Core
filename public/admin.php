<?php

declare(strict_types=1);

define('CATMIN_AREA', 'admin');
require dirname(__DIR__) . '/bootstrap.php';

$router = new Core\router\Router();
$kernel = new Core\kernel\Kernel($router);
$response = $kernel->handle(Core\http\Request::capture());
$response->send();
