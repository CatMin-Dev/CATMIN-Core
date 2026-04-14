<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/bootstrap.php';

use Core\logs\Logger;

$maxSizeBytes = 5 * 1024 * 1024;
$logDirs = [
    CATMIN_STORAGE . '/logs',
    CATMIN_ROOT . '/logs',
];

$rotated = 0;
$checked = 0;
$errors = [];

$logFiles = [];
foreach ($logDirs as $dir) {
    if (!is_dir($dir)) {
        continue;
    }
    $matches = glob(rtrim($dir, '/') . '/*.log');
    if (!is_array($matches)) {
        continue;
    }
    foreach ($matches as $file) {
        if (!is_file($file)) {
            continue;
        }
        $real = (string) realpath($file);
        $logFiles[$real !== '' ? $real : $file] = $file;
    }
}

foreach (array_values($logFiles) as $file) {
    $checked++;
    $size = (int) (@filesize($file) ?: 0);
    if ($size < $maxSizeBytes) {
        continue;
    }

    $archive = $file . '.' . date('Ymd-His');
    if (@rename($file, $archive)) {
        @file_put_contents($file, '');
        @chmod($file, 0664);
        $rotated++;
    } else {
        $errors[] = $file;
    }
}

Logger::info('Cron core-logs-rotate executed', [
    'checked_files' => $checked,
    'rotated_files' => $rotated,
    'errors' => $errors,
]);

echo 'core-logs-rotate done: checked=' . $checked . ' rotated=' . $rotated . ' errors=' . count($errors) . PHP_EOL;

