<?php

declare(strict_types=1);

namespace Core\versioning;

final class Version
{
    public static function current(): string
    {
        $path = CATMIN_ROOT . '/version.json';
        if (!is_file($path)) {
            return '0.0.0-dev.0';
        }

        $raw = file_get_contents($path);
        if ($raw === false) {
            return '0.0.0-dev.0';
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return '0.0.0-dev.0';
        }

        return (string) ($decoded['version'] ?? '0.0.0-dev.0');
    }
}
