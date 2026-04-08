<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/bootstrap.php';

use Core\logs\Logger;

$maxSizeBytes = 5 * 1024 * 1024;
$logs = [
    CATMIN_STORAGE . '/logs/catmin.log',
    CATMIN_ROOT . '/logs/catmin.log',
];

$rotated = 0;

foreach ($logs as $file) {
    if (!is_file($file)) {
        continue;
    }
    $size = (int) (@filesize($file) ?: 0);
    if ($size < $maxSizeBytes) {
        continue;
    }

    $archive = $file . '.' . date('Ymd-His');
    if (@rename($file, $archive)) {
        @file_put_contents($file, '');
        $rotated++;
    }
}

Logger::info('Cron core-logs-rotate executed', [
    'rotated_files' => $rotated,
]);

echo 'core-logs-rotate done: rotated=' . $rotated . PHP_EOL;

