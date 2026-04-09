<?php

declare(strict_types=1);

use Core\database\ConnectionManager;

final class CoreTelemetryMinimal
{
    private string $table;

    public function __construct()
    {
        $this->table = (string) config('database.prefixes.core', 'core_') . 'telemetry_reports';
    }

    public function isEnabled(): bool
    {
        $env = strtolower(trim((string) env('TELEMETRY_ENABLED', '0')));
        if (in_array($env, ['1', 'true', 'yes', 'on'], true)) {
            return true;
        }
        if (in_array($env, ['0', 'false', 'no', 'off'], true)) {
            return false;
        }
        return false;
    }

    public function buildSnapshot(array $extra = []): array
    {
        require_once CATMIN_CORE . '/versioning/Version.php';
        $version = \Core\versioning\Version::current();
        $modules = $this->countEnabledModules();
        $updates = $this->hasPendingUpdates();

        $payload = [
            'core_version' => $version,
            'php_version' => PHP_VERSION,
            'env' => (string) config('app.env', 'production'),
            'modules_enabled_count' => $modules,
            'updates_pending' => $updates,
            'timestamp' => gmdate('c'),
        ];

        foreach ($extra as $key => $value) {
            if (in_array((string) $key, ['instance_id', 'admin_email', 'ip_address', 'users', 'db_password'], true)) {
                continue;
            }
            $payload[(string) $key] = $value;
        }

        return $payload;
    }

    public function store(array $payload, string $channel = 'minimal'): bool
    {
        try {
            $pdo = (new ConnectionManager())->connection();
            $stmt = $pdo->prepare(
                'INSERT INTO ' . $this->table . ' (channel, payload, created_at) VALUES (:channel, :payload, CURRENT_TIMESTAMP)'
            );
            return $stmt->execute([
                'channel' => mb_substr(trim($channel), 0, 60),
                'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}',
            ]);
        } catch (\Throwable) {
            return false;
        }
    }

    private function countEnabledModules(): int
    {
        require_once CATMIN_CORE . '/module-loader.php';
        $scan = (new CoreModuleLoader())->scan();
        $rows = is_array($scan['modules'] ?? null) ? $scan['modules'] : [];
        return count(array_filter($rows, static fn (array $row): bool => (bool) ($row['enabled'] ?? false)));
    }

    private function hasPendingUpdates(): bool
    {
        require_once CATMIN_CORE . '/updater.php';
        $core = (new CoreUpdater())->check();
        if ((bool) ($core['update_available'] ?? false)) {
            return true;
        }
        require_once CATMIN_CORE . '/market-engine.php';
        $catalog = (new CoreMarketEngine())->catalog();
        $items = is_array($catalog['items'] ?? null) ? $catalog['items'] : [];
        foreach ($items as $item) {
            if ((bool) ($item['has_update'] ?? false)) {
                return true;
            }
        }
        return false;
    }
}

