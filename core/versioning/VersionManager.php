<?php

declare(strict_types=1);

namespace Core\versioning;

require_once CATMIN_CORE . '/db-version-manager.php';
require_once CATMIN_CORE . '/versioning/Version.php';
require_once CATMIN_CORE . '/versioning/VersionHistory.php';
require_once CATMIN_CORE . '/versioning/ModuleCompatibility.php';

final class VersionManager
{
    public function core(): string
    {
        return Version::current();
    }

    public function db(): string
    {
        return (new \CoreDbVersionManager())->currentSchemaVersion();
    }

    public function dbExpected(): string
    {
        return (new \CoreDbVersionManager())->expectedSchemaVersion();
    }

    public function history(): array
    {
        $historyFile = (string) config('versioning.history_file', base_path('logs/version-history.json'));
        if (!is_file($historyFile)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($historyFile), true);
        return is_array($decoded) ? $decoded : [];
    }

    public function moduleCompatibility(): array
    {
        return (new ModuleCompatibility())->report();
    }

    public function formatValid(string $version): bool
    {
        return preg_match('/^[0-9]+\.[0-9]+\.[0-9]+(?:-[a-z]+(?:\.[0-9]+)?)?$/i', trim($version)) === 1;
    }

    public function snapshot(): array
    {
        return [
            'core' => $this->core(),
            'db' => $this->db(),
            'db_expected' => $this->dbExpected(),
            'modules' => $this->moduleCompatibility(),
            'history_count' => count($this->history()),
        ];
    }
}

