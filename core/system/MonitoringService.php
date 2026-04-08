<?php

declare(strict_types=1);

namespace Core\system;

use Core\database\ConnectionManager;

final class MonitoringService
{
    public function snapshot(): array
    {
        $health = (new HealthCheckService())->run();

        $criticalErrors = 0;
        $latestCritical = '-';
        $securityAlerts = 0;
        $latestSecurity = '-';
        $maintenanceActive = false;
        $maintenanceMeta = '-';
        $moduleIssues = 0;

        try {
            $pdo = (new ConnectionManager())->connection();
            $corePrefix = (string) config('database.prefixes.core', 'core_');
            $adminPrefix = (string) config('database.prefixes.admin', 'admin_');

            $logsTable = $corePrefix . 'logs';
            $securityTable = $adminPrefix . 'security_events';
            $settingsTable = $corePrefix . 'settings';

            $stmtCritical = $pdo->query('SELECT COUNT(*) FROM ' . $logsTable . ' WHERE level IN (\'ERROR\', \'CRITICAL\')');
            $criticalErrors = (int) (($stmtCritical !== false ? $stmtCritical->fetchColumn() : 0) ?: 0);
            $stmtLatestCritical = $pdo->query('SELECT message FROM ' . $logsTable . ' WHERE level IN (\'ERROR\', \'CRITICAL\') ORDER BY created_at DESC LIMIT 1');
            $latestCritical = (string) (($stmtLatestCritical !== false ? $stmtLatestCritical->fetchColumn() : '-') ?: '-');

            $stmtSecurityCount = $pdo->query('SELECT COUNT(*) FROM ' . $securityTable . ' WHERE severity IN (\'warning\', \'error\', \'critical\')');
            $securityAlerts = (int) (($stmtSecurityCount !== false ? $stmtSecurityCount->fetchColumn() : 0) ?: 0);
            $stmtSecurityLatest = $pdo->query('SELECT event_type FROM ' . $securityTable . ' ORDER BY created_at DESC LIMIT 1');
            $latestSecurity = (string) (($stmtSecurityLatest !== false ? $stmtSecurityLatest->fetchColumn() : '-') ?: '-');

            $stmtMaint = $pdo->prepare('SELECT setting_key, setting_value FROM ' . $settingsTable . ' WHERE category = :category AND setting_key IN (\'enabled\', \'started_at\')');
            $stmtMaint->execute(['category' => 'maintenance']);
            $rows = $stmtMaint->fetchAll(\PDO::FETCH_ASSOC);
            $map = [];
            if (is_array($rows)) {
                foreach ($rows as $row) {
                    $map[(string) ($row['setting_key'] ?? '')] = (string) ($row['setting_value'] ?? '');
                }
            }
            $maintenanceActive = in_array(strtolower((string) ($map['enabled'] ?? '0')), ['1', 'true', 'yes', 'on'], true);
            $maintenanceMeta = (string) ($map['started_at'] ?? '-');
        } catch (\Throwable) {
        }

        try {
            $scan = (new \CoreModuleLoader())->scan();
            foreach ((array) ($scan['modules'] ?? []) as $module) {
                $errors = (array) ($module['errors'] ?? []);
                $state = (string) ($module['state'] ?? '');
                if ($errors !== [] || in_array($state, ['error', 'invalid', 'incompatible'], true)) {
                    $moduleIssues++;
                }
            }
        } catch (\Throwable) {
        }

        return [
            'status' => $health['global'] ?? 'unknown',
            'health' => $health,
            'widgets' => [
                'critical_errors' => ['count' => $criticalErrors, 'last' => $latestCritical],
                'security_alerts' => ['count' => $securityAlerts, 'last' => $latestSecurity],
                'maintenance' => ['active' => $maintenanceActive, 'meta' => $maintenanceMeta],
                'module_issues' => ['count' => $moduleIssues],
            ],
        ];
    }
}

