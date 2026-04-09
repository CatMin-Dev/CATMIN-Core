<?php

declare(strict_types=1);

final class CoreModuleUninstallLogger
{
    public function log(string $event, array $context = []): void
    {
        $dir = CATMIN_STORAGE . '/modules/uninstall-logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $line = json_encode([
            'ts' => gmdate('c'),
            'event' => $event,
            'context' => $context,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (is_string($line)) {
            @file_put_contents($dir . '/uninstall-' . gmdate('Y-m-d') . '.log', $line . PHP_EOL, FILE_APPEND);
        }
        Core\logs\Logger::warning('Module uninstall event', ['event' => $event, 'context' => $context]);
    }
}

