<?php

declare(strict_types=1);

final class CoreModuleIntegrityReporter
{
    public function write(array $report): void
    {
        $dir = CATMIN_STORAGE . '/modules/integrity-reports';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $encoded = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($encoded)) {
            return;
        }

        @file_put_contents($dir . '/latest.json', $encoded . PHP_EOL);
        @file_put_contents($dir . '/integrity-' . gmdate('Ymd-His') . '.json', $encoded . PHP_EOL);
    }

    public function latest(): array
    {
        $file = CATMIN_STORAGE . '/modules/integrity-reports/latest.json';
        if (!is_file($file)) {
            return [];
        }
        $decoded = json_decode((string) file_get_contents($file), true);
        return is_array($decoded) ? $decoded : [];
    }
}

