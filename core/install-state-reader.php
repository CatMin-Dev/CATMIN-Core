<?php

declare(strict_types=1);

final class CoreInstallStateReader
{
    public static function latestReport(): array
    {
        $file = CATMIN_STORAGE . '/install/reports/latest.json';
        if (!is_file($file)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($file), true);
        return is_array($decoded) ? $decoded : [];
    }

    public static function lockPayload(): array
    {
        $file = CATMIN_STORAGE . '/install/installed.lock';
        if (!is_file($file)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($file), true);
        return is_array($decoded) ? $decoded : [];
    }
}

