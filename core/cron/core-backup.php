<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/bootstrap.php';

use Core\database\ConnectionManager;
use Core\logs\Logger;
use Core\versioning\Version;

$dir = CATMIN_STORAGE . '/backups';
if (!is_dir($dir)) {
    @mkdir($dir, 0775, true);
}

$driver = (string) config('database.default', 'sqlite');
$stamp = date('YmdHis');
$filename = $stamp . '.json';
$target = $dir . '/' . $filename;

$payload = [
    'generated_at' => date('c'),
    'source' => 'cron.core-backup',
    'driver' => $driver,
    'version' => Version::current(),
];

$ok = @file_put_contents(
    $target,
    (string) json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL
) !== false;

if ($ok) {
    try {
        $pdo = (new ConnectionManager())->connection();
        $table = (string) config('database.prefixes.core', 'core_') . 'backups';
        $insert = $pdo->prepare(
            'INSERT INTO ' . $table . ' (backup_type, status, file_path, checksum, size_bytes, created_at) VALUES (:backup_type, :status, :file_path, :checksum, :size_bytes, :created_at)'
        );
        $size = (int) (@filesize($target) ?: 0);
        $checksum = (string) (@hash_file('sha256', $target) ?: '');
        $insert->execute([
            'backup_type' => 'auto',
            'status' => 'success',
            'file_path' => $target,
            'checksum' => $checksum !== '' ? $checksum : null,
            'size_bytes' => $size,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    } catch (\Throwable $e) {
        Logger::error('Cron core-backup DB insert failed', ['error' => substr($e->getMessage(), 0, 160)]);
    }
}

Logger::info('Cron core-backup executed', [
    'status' => $ok ? 'ok' : 'error',
    'file' => $filename,
    'driver' => $driver,
]);

echo 'core-backup status=' . ($ok ? 'ok' : 'error') . ' file=' . $filename . PHP_EOL;
