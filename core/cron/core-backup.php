<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/bootstrap.php';

use Core\database\ConnectionManager;
use Core\logs\Logger;
use Core\maintenance\BackupManager;

try {
    $pdo = (new ConnectionManager())->connection();
    $corePrefix = (string) config('database.prefixes.core', 'core_');
    $manager = new BackupManager($pdo, $corePrefix . 'backups', $corePrefix . 'maintenance_audit');

    $result = $manager->createBackup('db_only', [
        'origin' => 'cron.core-backup',
        'user_id' => 0,
        'username' => 'cron',
        'ip' => '127.0.0.1',
    ]);

    $ok = (bool) ($result['ok'] ?? false);
    $file = (string) ($result['name'] ?? '');

    Logger::info('Cron core-backup executed', [
        'status' => $ok ? 'ok' : 'error',
        'file' => $file,
        'message' => (string) ($result['message'] ?? ''),
    ]);

    echo 'core-backup status=' . ($ok ? 'ok' : 'error') . ' file=' . $file . PHP_EOL;
    if (!$ok) {
        exit(1);
    }
} catch (\Throwable $e) {
    Logger::error('Cron core-backup failed', ['error' => $e->getMessage()]);
    echo 'core-backup status=error file=' . '' . PHP_EOL;
    exit(1);
}
