<?php

declare(strict_types=1);

namespace Core\system;

use Core\database\ConnectionManager;
use Core\versioning\Version;

require_once CATMIN_CORE . '/module-loader.php';
require_once CATMIN_CORE . '/module-state-store.php';

final class HealthCheckService
{
    public function run(): array
    {
        $checks = [];
        $this->addCoreChecks($checks);
        $this->addEnvironmentChecks($checks);
        $this->addDatabaseChecks($checks);
        $this->addSecurityChecks($checks);
        $this->addStorageChecks($checks);
        $this->addModulesChecks($checks);
        $this->addCronChecks($checks);

        $summary = ['healthy' => 0, 'warning' => 0, 'critical' => 0, 'unknown' => 0];
        foreach ($checks as $check) {
            $status = (string) ($check['status'] ?? 'unknown');
            if (!array_key_exists($status, $summary)) {
                $status = 'unknown';
            }
            $summary[$status]++;
        }

        $global = 'healthy';
        if ($summary['critical'] > 0) {
            $global = 'critical';
        } elseif ($summary['warning'] > 0) {
            $global = 'warning';
        } elseif ($summary['unknown'] > 0) {
            $global = 'unknown';
        }

        return [
            'global' => $global,
            'summary' => $summary,
            'checks' => $checks,
        ];
    }

    private function addCoreChecks(array &$checks): void
    {
        $checks[] = $this->mk('core.boot', 'Core boot', 'healthy', 'Bootstrap chargé.');
        $checks[] = $this->mk('core.routing', 'Routing', 'healthy', 'Routeur opérationnel.');
        $checks[] = $this->mk('core.version', 'Version core', Version::current() !== '' ? 'healthy' : 'critical', 'Version: ' . Version::current());
    }

    private function addEnvironmentChecks(array &$checks): void
    {
        $checks[] = $this->mk('env.php', 'PHP >= 8.3.0', PHP_VERSION_ID >= 80300 ? 'healthy' : 'critical', 'Version: ' . PHP_VERSION);

        $required = ['pdo', 'mbstring', 'json', 'fileinfo', 'openssl', 'curl', 'gd', 'intl', 'session', 'ctype', 'filter', 'hash', 'tokenizer', 'sodium'];
        $missing = array_values(array_filter($required, static fn (string $ext): bool => !extension_loaded($ext)));
        $checks[] = $this->mk(
            'env.extensions',
            'Extensions PHP critiques',
            $missing === [] ? 'healthy' : 'critical',
            $missing === [] ? 'Toutes présentes' : ('Manquantes: ' . implode(', ', $missing))
        );

        $timezone = $this->resolveTimezone();
        $checks[] = $this->mk('env.timezone', 'Timezone', $timezone !== '' ? 'healthy' : 'warning', $timezone !== '' ? $timezone : 'Non définie');
    }

    private function resolveTimezone(): string
    {
        try {
            $pdo = (new ConnectionManager())->connection();
            $table = (string) config('database.prefixes.core', 'core_') . 'settings';
            $stmt = $pdo->prepare(
                'SELECT category, setting_value FROM ' . $table . " WHERE setting_key = 'timezone' AND category IN ('general', 'system')"
            );
            $stmt->execute();
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $rows = is_array($rows) ? $rows : [];

            $map = [];
            foreach ($rows as $row) {
                $category = (string) ($row['category'] ?? '');
                $value = trim((string) ($row['setting_value'] ?? ''));
                if ($category !== '' && $value !== '') {
                    $map[$category] = $value;
                }
            }

            if (isset($map['general']) && in_array($map['general'], \DateTimeZone::listIdentifiers(), true)) {
                return $map['general'];
            }
            if (isset($map['system']) && in_array($map['system'], \DateTimeZone::listIdentifiers(), true)) {
                return $map['system'];
            }
        } catch (\Throwable) {
        }

        return (string) config('app.timezone', 'UTC');
    }

    private function addDatabaseChecks(array &$checks): void
    {
        try {
            $pdo = (new ConnectionManager())->connection();
            $driver = (string) $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
            $checks[] = $this->mk('db.connection', 'Connexion DB', 'healthy', 'Driver: ' . $driver);

            $corePrefix = (string) config('database.prefixes.core', 'core_');
            $adminPrefix = (string) config('database.prefixes.admin', 'admin_');
            $required = [$corePrefix . 'settings', $corePrefix . 'db_versions', $adminPrefix . 'users', $adminPrefix . 'security_events'];
            $missing = [];
            foreach ($required as $table) {
                try {
                    $pdo->query('SELECT 1 FROM ' . $table . ' LIMIT 1');
                } catch (\Throwable) {
                    $missing[] = $table;
                }
            }

            $checks[] = $this->mk(
                'db.tables',
                'Tables critiques',
                $missing === [] ? 'healthy' : 'critical',
                $missing === [] ? 'Tables présentes' : ('Manquantes: ' . implode(', ', $missing))
            );

            $dbCurrent = 'unknown';
            try {
                $table = $corePrefix . 'db_versions';
                $stmt = $pdo->query('SELECT schema_version FROM ' . $table . ' ORDER BY id DESC LIMIT 1');
                $dbCurrent = (string) (($stmt !== false ? $stmt->fetchColumn() : null) ?: 'unknown');
            } catch (\Throwable) {
                $dbCurrent = 'unknown';
            }
            $dbExpected = (string) config('database.schema_version', 'unknown');
            $checks[] = $this->mk('db.version', 'Version DB', $dbCurrent === $dbExpected ? 'healthy' : 'critical', 'Actuelle: ' . $dbCurrent . ' / Attendue: ' . $dbExpected);
        } catch (\Throwable $e) {
            $checks[] = $this->mk('db.connection', 'Connexion DB', 'critical', 'Indisponible: ' . substr($e->getMessage(), 0, 120));
        }
    }

    private function addSecurityChecks(array &$checks): void
    {
        $installLock = is_file(CATMIN_STORAGE . '/install.lock') || is_file(CATMIN_STORAGE . '/install/installed.lock');
        $checks[] = $this->mk('security.install_lock', 'Install lock', $installLock ? 'healthy' : 'critical', $installLock ? 'Actif' : 'Absent');
        $checks[] = $this->mk('security.admin_path', 'Route admin', trim((string) config('security.admin_path', 'admin'), '/') !== '' ? 'healthy' : 'warning', '/' . trim((string) config('security.admin_path', 'admin'), '/'));
        $checks[] = $this->mk('security.maintenance', 'Maintenance', $this->maintenanceEnabled() ? 'warning' : 'healthy', $this->maintenanceEnabled() ? 'Active' : 'Inactive');
    }

    private function addStorageChecks(array &$checks): void
    {
        $checks[] = $this->mk('storage.root', 'storage writable', is_writable(CATMIN_STORAGE) ? 'healthy' : 'critical', CATMIN_STORAGE);

        $folders = [
            'logs' => CATMIN_STORAGE . '/logs',
            'cache' => CATMIN_ROOT . '/cache',
            'sessions' => CATMIN_STORAGE . '/sessions',
            'backups' => CATMIN_STORAGE . '/backups',
        ];
        foreach ($folders as $label => $path) {
            if (!is_dir($path)) {
                $checks[] = $this->mk('storage.' . $label, ucfirst($label) . ' writable', 'warning', 'Dossier absent: ' . $path);
                continue;
            }
            $checks[] = $this->mk('storage.' . $label, ucfirst($label) . ' writable', is_writable($path) ? 'healthy' : 'critical', $path);
        }
    }

    private function addModulesChecks(array &$checks): void
    {
        try {
            $loader = new \CoreModuleLoader();
            $snapshot = $loader->scan();
            $modules = (array) ($snapshot['modules'] ?? []);
            $invalid = 0;
            $knownSlugs = [];
            foreach ($modules as $module) {
                $errors = (array) ($module['errors'] ?? []);
                $state = (string) ($module['state'] ?? '');
                $slug = strtolower(trim((string) ($module['manifest']['slug'] ?? '')));
                if ($slug !== '') {
                    $knownSlugs[$slug] = true;
                }
                if ($errors !== [] || in_array($state, ['error', 'invalid', 'incompatible'], true)) {
                    $invalid++;
                }
            }

            $stateBySlug = (new \CoreModuleStateStore())->stateBySlug();
            $orphaned = [];
            foreach (array_keys($stateBySlug) as $dbSlug) {
                $dbState = strtolower(trim((string) ($stateBySlug[$dbSlug]['db_state'] ?? '')));
                if (in_array($dbState, ['uninstalled_keep_data', 'uninstalled_drop_data'], true)) {
                    continue;
                }
                if (!isset($knownSlugs[$dbSlug])) {
                    $orphaned[] = $dbSlug;
                }
            }

            $status = ($invalid > 0 || $orphaned !== []) ? 'warning' : 'healthy';
            $detail = [];
            if ($invalid > 0) {
                $detail[] = $invalid . ' module(s) en anomalie';
            }
            if ($orphaned !== []) {
                $detail[] = count($orphaned) . ' état(s) orphelin(s)';
            }
            if ($detail === []) {
                $detail[] = 'Modules cohérents';
            }

            $checks[] = $this->mk('modules.validity', 'Modules', $status, implode(' | ', $detail));
        } catch (\Throwable $e) {
            $checks[] = $this->mk('modules.validity', 'Modules', 'warning', 'Check indisponible: ' . substr($e->getMessage(), 0, 120));
        }
    }

    private function addCronChecks(array &$checks): void
    {
        try {
            $pdo = (new ConnectionManager())->connection();
            $logsTable = (string) config('database.prefixes.core', 'core_') . 'logs';
            $stmt = $pdo->query('SELECT created_at FROM ' . $logsTable . ' WHERE channel = \'cron\' ORDER BY created_at DESC LIMIT 1');
            $lastRun = (string) (($stmt !== false ? $stmt->fetchColumn() : null) ?: '');
            if ($lastRun === '') {
                $checks[] = $this->mk('cron.last_run', 'Cron dernier run', 'warning', 'Aucune exécution détectée');
                return;
            }
            $checks[] = $this->mk('cron.last_run', 'Cron dernier run', 'healthy', $lastRun);
        } catch (\Throwable) {
            $checks[] = $this->mk('cron.last_run', 'Cron dernier run', 'unknown', 'Indisponible');
        }
    }

    private function maintenanceEnabled(): bool
    {
        try {
            $pdo = (new ConnectionManager())->connection();
            $table = (string) config('database.prefixes.core', 'core_') . 'settings';
            $stmt = $pdo->prepare('SELECT setting_value FROM ' . $table . ' WHERE category = :category AND setting_key = :setting_key LIMIT 1');
            $stmt->execute(['category' => 'maintenance', 'setting_key' => 'enabled']);
            $value = (string) ($stmt->fetchColumn() ?: '0');
            return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
        } catch (\Throwable) {
            return false;
        }
    }

    private function mk(string $id, string $label, string $status, string $detail): array
    {
        return [
            'id' => $id,
            'label' => $label,
            'status' => in_array($status, ['healthy', 'warning', 'critical', 'unknown'], true) ? $status : 'unknown',
            'detail' => $detail,
        ];
    }
}
