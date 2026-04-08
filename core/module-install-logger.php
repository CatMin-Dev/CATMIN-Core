<?php

declare(strict_types=1);

require_once CATMIN_CORE . '/module-staging-manager.php';
require_once CATMIN_CORE . '/logs/Logger.php';

final class CoreModuleInstallLogger
{
    public function log(string $action, string $status, array $context = []): void
    {
        $manager = new CoreModuleStagingManager();
        $manager->ensure();
        $row = [
            'at' => gmdate('c'),
            'action' => $action,
            'status' => $status,
            'context' => $context,
        ];
        $line = json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (is_string($line)) {
            @file_put_contents($manager->installLogsDir() . '/module-install-' . gmdate('Ymd') . '.log', $line . PHP_EOL, FILE_APPEND | LOCK_EX);
        }
        Core\logs\Logger::info('Module installer: ' . $action, ['status' => $status, 'context' => $context]);
    }
}

