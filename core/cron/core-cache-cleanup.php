<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/bootstrap.php';

use Core\logs\Logger;

$targets = [
    CATMIN_ROOT . '/cache',
    CATMIN_ROOT . '/tmp',
    CATMIN_STORAGE . '/tmp',
];

$removed = 0;
$errors = 0;
$threshold = time() - 86400;

foreach ($targets as $target) {
    if (!is_dir($target)) {
        continue;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($target, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $entry) {
        if (!$entry instanceof SplFileInfo || !$entry->isFile()) {
            continue;
        }
        if ($entry->getMTime() >= $threshold) {
            continue;
        }
        if (@unlink($entry->getPathname())) {
            $removed++;
        } else {
            $errors++;
        }
    }
}

Logger::info('Cron core-cache-cleanup executed', [
    'removed_files' => $removed,
    'errors' => $errors,
]);

echo 'core-cache-cleanup done: removed=' . $removed . ', errors=' . $errors . PHP_EOL;

