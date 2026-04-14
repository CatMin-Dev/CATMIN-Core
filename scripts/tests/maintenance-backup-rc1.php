<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once CATMIN_CORE . '/maintenance/BackupManager.php';

use Core\database\ConnectionManager;
use Core\maintenance\BackupManager;

$pdo = (new ConnectionManager())->connection();
$corePrefix = (string) config('database.prefixes.core', 'core_');
$manager = new BackupManager($pdo, $corePrefix . 'backups', $corePrefix . 'maintenance_audit');

$assert = static function (bool $ok, string $message): void {
    if (!$ok) {
        fwrite(STDERR, "[FAIL] " . $message . PHP_EOL);
        exit(1);
    }
    echo "[OK] " . $message . PHP_EOL;
};

$ctx = ['user_id' => 1, 'username' => 'test-admin', 'ip' => '127.0.0.1', 'origin' => 'manual-test'];

$created = $manager->createBackup('db_only', $ctx);
$assert((bool) ($created['ok'] ?? false), 'create backup db_only');

$name = (string) ($created['name'] ?? '');
$assert($name !== '', 'backup name returned');

$read = $manager->readBackup($name);
$assert((bool) ($read['ok'] ?? false), 'read backup details');

$dry = $manager->restoreBackup($name, 'db_only', true, false, $ctx);
$assert((bool) ($dry['ok'] ?? false), 'restore dry-run db_only');

$list = $manager->listBackups(20);
$assert(is_array($list) && count($list) > 0, 'list backups returns entries');

$repaired = $manager->deleteBackup('missing-file-does-not-exist.zip', $ctx, true);
$assert(!(bool) ($repaired['ok'] ?? false), 'orphan repair on unknown backup fails cleanly');

$deleted = $manager->deleteBackup($name, $ctx, false);
$assert((bool) ($deleted['ok'] ?? false), 'delete backup normal');

$audit = $manager->auditLog(30);
$assert(is_array($audit) && count($audit) > 0, 'audit log generated');

echo '[DONE] maintenance backup rc1 smoke tests' . PHP_EOL;
