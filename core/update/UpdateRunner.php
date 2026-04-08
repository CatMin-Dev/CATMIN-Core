<?php

declare(strict_types=1);

namespace Core\update;

use Core\logs\Logger;

require_once CATMIN_CORE . '/update/UpdatePreflight.php';
require_once CATMIN_CORE . '/db-upgrade-runner.php';

final class UpdateRunner
{
    public function run(?string $targetCoreVersion = null, bool $dryRun = true): array
    {
        $preflight = (new UpdatePreflight())->run($targetCoreVersion);
        $report = [
            'date' => date('c'),
            'dry_run' => $dryRun,
            'preflight' => $preflight,
            'upgrade' => null,
        ];

        if (!$preflight['ok']) {
            $this->writeReport($report);
            Logger::error('Update preflight failed', ['errors' => $preflight['errors']]);
            return $report;
        }

        if (!$dryRun) {
            $report['upgrade'] = (new \CoreDbUpgradeRunner())->run();
            Logger::info('Update upgrade executed', ['upgrade' => $report['upgrade']]);
        }

        $this->writeReport($report);
        return $report;
    }

    private function writeReport(array $report): void
    {
        $dir = CATMIN_STORAGE . '/updates/reports';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $name = 'update-report-' . date('Ymd-His') . '.json';
        $json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (is_string($json)) {
            @file_put_contents($dir . '/' . $name, $json . PHP_EOL, LOCK_EX);
            @file_put_contents($dir . '/latest.json', $json . PHP_EOL, LOCK_EX);
        }
    }
}

