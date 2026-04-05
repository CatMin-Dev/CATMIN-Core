<?php

declare(strict_types=1);

define('CATMIN_AREA', 'install');
require dirname(__DIR__) . '/bootstrap.php';

header('X-Robots-Tag: noindex, nofollow');

$lockFile = CATMIN_STORAGE . '/install/installed.lock';
if (is_file($lockFile)) {
    http_response_code(403);
    echo 'Installer is locked.';
    exit;
}

require CATMIN_INSTALL . '/views/welcome.php';
