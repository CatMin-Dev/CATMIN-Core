<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/bootstrap.php';

use Core\database\ConnectionManager;
use Core\logs\Logger;

$status = [
    'db' => 'down',
    'storage_writable' => is_writable(CATMIN_STORAGE),
    'cache_writable' => is_writable(CATMIN_ROOT . '/cache'),
    'tmp_writable' => is_writable(CATMIN_ROOT . '/tmp'),
];

try {
    $pdo = (new ConnectionManager())->connection();
    $pdo->query('SELECT 1');
    $status['db'] = 'ok';
} catch (\Throwable $e) {
    $status['db'] = 'error';
    $status['db_error'] = substr($e->getMessage(), 0, 160);
}

$ok = $status['db'] === 'ok'
    && $status['storage_writable']
    && $status['cache_writable']
    && $status['tmp_writable'];

Logger::info('Cron core-health-check executed', [
    'status' => $ok ? 'ok' : 'warning',
    'checks' => $status,
]);

echo 'core-health-check status=' . ($ok ? 'ok' : 'warning') . PHP_EOL;

