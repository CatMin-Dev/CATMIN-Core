<?php

declare(strict_types=1);

final class CoreModuleSnapshotLogger
{
    public function log(string $event, array $context = []): void
    {
        if (!is_dir(CATMIN_STORAGE . '/modules')) {
            @mkdir(CATMIN_STORAGE . '/modules', 0775, true);
        }
        $line = json_encode([
            'ts' => gmdate('c'),
            'event' => $event,
            'context' => $context,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (is_string($line)) {
            @file_put_contents(CATMIN_STORAGE . '/modules/snapshot.log', $line . PHP_EOL, FILE_APPEND);
        }
        Core\logs\Logger::info('Module snapshot event', ['event' => $event, 'context' => $context]);
    }
}

