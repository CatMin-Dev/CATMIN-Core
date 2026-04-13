<?php

declare(strict_types=1);

use Admin\controllers\AuthController;
use Core\database\ConnectionManager;
use Core\http\Request;
use Core\http\Response;
use Core\http\View;
use Core\security\SecurityManager;
use Core\system\HealthCheckService;
use Core\system\MonitoringService;
use Core\versioning\Version;

require_once CATMIN_CORE . '/module-loader.php';
require_once CATMIN_CORE . '/module-activator.php';
require_once CATMIN_CORE . '/module-integrity-scanner.php';
require_once CATMIN_CORE . '/settings-engine.php';
require_once CATMIN_CORE . '/i18n-engine.php';
require_once CATMIN_CORE . '/notifications-bridge.php';
require_once CATMIN_CORE . '/notifications-dispatcher.php';
require_once CATMIN_CORE . '/apps-repository.php';
require_once CATMIN_CORE . '/apps-validator.php';
require_once CATMIN_CORE . '/db-upgrade-runner.php';
require_once CATMIN_CORE . '/updater.php';
require_once CATMIN_CORE . '/market-engine.php';
require_once CATMIN_CORE . '/update-center.php';
require_once CATMIN_CORE . '/module-repository-registry.php';
require_once CATMIN_CORE . '/module-uninstaller.php';
require_once CATMIN_CORE . '/module-snapshot-manager.php';
require_once CATMIN_CORE . '/module-rollback-runner.php';
require_once CATMIN_CORE . '/trust-center.php';
require_once CATMIN_CORE . '/queue-engine.php';
require_once CATMIN_CORE . '/update-intelligent-notifier.php';
require_once CATMIN_CORE . '/telemetry-minimal.php';

$security = new SecurityManager(Request::capture(), 'admin');
$authRequired = $security->adminAuthRequiredMiddleware();
$csrfCheck = $security->csrfCheckMiddleware();
$recentPassword = $security->recentPasswordRequiredMiddleware();

$adminPrefix = (string) config('database.prefixes.admin', 'admin_');
$corePrefix = (string) config('database.prefixes.core', 'core_');
$rolesTable = $adminPrefix . 'roles';
$usersTable = $adminPrefix . 'users';
$permissionsTable = $adminPrefix . 'permissions';
$rolePermissionsTable = $adminPrefix . 'role_permissions';
$eventsTable = $adminPrefix . 'security_events';
$coreSettingsTable = $corePrefix . 'settings';
$coreLogsTable = $corePrefix . 'logs';
$coreBackupsTable = $corePrefix . 'backups';
$coreCronTasksTable = $corePrefix . 'cron_tasks';

$pushFlash = static function (string $message, string $type = 'success'): void {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }
    $_SESSION['catmin_admin_flash'] = [
        'message' => $message,
        'type' => $type,
    ];
};

$consumeFlash = static function (): array {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }
    $flash = $_SESSION['catmin_admin_flash'] ?? null;
    unset($_SESSION['catmin_admin_flash']);
    if (!is_array($flash)) {
        return ['message' => '', 'type' => 'success'];
    }
    return [
        'message' => (string) ($flash['message'] ?? ''),
        'type' => (string) ($flash['type'] ?? 'success'),
    ];
};

$appsRepository = new CoreAppsRepository();
$appsValidator = new CoreAppsValidator();
$notificationsRepository = new CoreNotificationsRepository();

$redirect = static function (string $path, array $query = []) use ($pushFlash): Response {
    $flashMsg = trim((string) ($query['msg'] ?? ''));
    $flashType = trim((string) ($query['mt'] ?? 'success'));
    unset($query['msg'], $query['mt']);
    if ($flashMsg !== '') {
        $pushFlash($flashMsg, $flashType !== '' ? $flashType : 'success');
    }

    $qs = $query !== [] ? ('?' . http_build_query($query)) : '';
    return Response::html('', 302, ['Location' => $path . $qs]);
};

$fetchRoles = static function (\PDO $pdo) use ($rolesTable): array {
    $stmt = $pdo->query('SELECT id, name, slug, is_system FROM ' . $rolesTable . ' ORDER BY is_system DESC, name ASC');
    $rows = $stmt !== false ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];
    return is_array($rows) ? $rows : [];
};

$findRoleById = static function (\PDO $pdo, int $id) use ($rolesTable): ?array {
    $stmt = $pdo->prepare('SELECT id, name, slug, is_system FROM ' . $rolesTable . ' WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch(\PDO::FETCH_ASSOC);
    return is_array($row) ? $row : null;
};

$findStaffById = static function (\PDO $pdo, int $id) use ($usersTable, $rolesTable): ?array {
    $sql = 'SELECT u.id, u.role_id, u.username, u.email, u.is_active, u.last_login_at, u.created_at, u.updated_at, '
        . 'r.name AS role_name, r.slug AS role_slug, r.is_system AS role_is_system '
        . 'FROM ' . $usersTable . ' u '
        . 'LEFT JOIN ' . $rolesTable . ' r ON r.id = u.role_id '
        . 'WHERE u.id = :id LIMIT 1';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch(\PDO::FETCH_ASSOC);
    return is_array($row) ? $row : null;
};

$isSuperAdminUser = static fn (array $user): bool => (string) ($user['role_slug'] ?? '') === 'super-admin';
$isCriticalRole = static fn (array $role): bool => (bool) ($role['is_system'] ?? false) || (string) ($role['slug'] ?? '') === 'super-admin';

$buildPermissionsMatrix = static function (array $permissions): array {
    $matrix = [];
    foreach ($permissions as $permission) {
        $slug = (string) ($permission['slug'] ?? '');
        $parts = array_values(array_filter(explode('.', $slug), static fn (string $v): bool => $v !== ''));
        $action = $parts !== [] ? (string) array_pop($parts) : 'manage';
        $module = $parts !== [] ? implode('.', $parts) : 'core';

        if (!isset($matrix[$module])) {
            $matrix[$module] = [
                'module' => $module,
                'permissions' => [],
            ];
        }

        $matrix[$module]['permissions'][] = [
            'id' => (int) ($permission['id'] ?? 0),
            'slug' => $slug,
            'name' => (string) ($permission['name'] ?? $slug),
            'description' => (string) ($permission['description'] ?? ''),
            'action' => $action,
        ];
    }

    ksort($matrix);
    return array_values($matrix);
};

$resolveGitMeta = static function (): array {
    $gitDir = CATMIN_ROOT . '/.git';
    $headFile = $gitDir . '/HEAD';

    if (!is_file($headFile)) {
        return ['branch' => '-', 'commit' => '-'];
    }

    $headRaw = trim((string) file_get_contents($headFile));
    if ($headRaw === '') {
        return ['branch' => '-', 'commit' => '-'];
    }

    $branch = 'detached';
    $commit = '';
    if (str_starts_with($headRaw, 'ref: ')) {
        $ref = trim(substr($headRaw, 5));
        $branch = basename($ref);
        $refFile = $gitDir . '/' . $ref;
        if (is_file($refFile)) {
            $commit = trim((string) file_get_contents($refFile));
        } else {
            $packedRefs = $gitDir . '/packed-refs';
            if (is_file($packedRefs)) {
                $lines = (array) file($packedRefs, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    $line = trim((string) $line);
                    if ($line === '' || str_starts_with($line, '#') || str_starts_with($line, '^')) {
                        continue;
                    }
                    $parts = preg_split('/\s+/', $line);
                    if (is_array($parts) && count($parts) >= 2 && $parts[1] === $ref) {
                        $commit = (string) $parts[0];
                        break;
                    }
                }
            }
        }
    } else {
        $commit = $headRaw;
    }

    $commit = $commit !== '' ? substr($commit, 0, 12) : '-';

    return [
        'branch' => $branch !== '' ? $branch : '-',
        'commit' => $commit,
    ];
};

$scanModules = static function (): array {
    $loader = new CoreModuleLoader();
    $snapshot = $loader->scan();
    $integrityReport = (new CoreModuleIntegrityScanner())->scanAll(false);
    $integrityBySlug = [];
    foreach ((array) ($integrityReport['modules'] ?? []) as $integrityRow) {
        $integritySlug = strtolower(trim((string) ($integrityRow['slug'] ?? '')));
        if ($integritySlug !== '') {
            $integrityBySlug[$integritySlug] = $integrityRow;
        }
    }
    $rows = [];

    foreach ((array) ($snapshot['modules'] ?? []) as $module) {
        $manifest = (array) ($module['manifest'] ?? []);
        $slug = strtolower(trim((string) ($manifest['slug'] ?? '')));
        $scope = strtolower(trim((string) ($manifest['type'] ?? '')));
        $deps = $manifest['dependencies'] ?? [];
        $requires = [];
        if (is_array($deps)) {
            if (array_is_list($deps)) {
                $requires = array_map(static fn ($dep): string => strtolower(trim((string) $dep)), $deps);
            } else {
                $requires = array_map(static fn ($dep): string => strtolower(trim((string) $dep)), (array) ($deps['requires'] ?? []));
            }
        }
        $requires = array_values(array_filter($requires, static fn (string $dep): bool => $dep !== ''));
        $integrity = $integrityBySlug[$slug] ?? null;

        $rows[] = [
            'scope' => $scope !== '' ? $scope : 'unknown',
            'slug' => $slug !== '' ? $slug : 'unknown',
            'name' => (string) ($manifest['name'] ?? ucfirst($slug)),
            'version' => (string) ($manifest['version'] ?? '-'),
            'enabled' => (bool) ($module['enabled'] ?? false),
            'dependencies' => $requires,
            'errors' => (array) ($module['errors'] ?? []),
            'manifest_path' => (string) ($module['manifest_path'] ?? ''),
            'state' => (string) ($module['state'] ?? 'detected'),
            'integrity_status' => (string) (($integrity['integrity_status'] ?? 'unknown')),
            'signature_status' => (string) (($integrity['signature_status'] ?? 'unknown')),
            'trusted' => (bool) (($integrity['trusted'] ?? false)),
            'key_scope' => (string) (($integrity['key_scope'] ?? 'unknown')),
            'key_status' => (string) (($integrity['key_status'] ?? 'unknown')),
            'integrity_details' => is_array($integrity) ? (array) ($integrity['state'] ?? []) : [],
            'capabilities' => is_array($manifest['capabilities'] ?? null) ? array_values($manifest['capabilities']) : [],
            'dependency_details' => [],
            'dependency_blocking' => false,
            'missing_dependencies' => [],
        ];
    }

    $indexBySlug = [];
    foreach ($rows as $entry) {
        $indexBySlug[(string) ($entry['slug'] ?? '')] = $entry;
    }
    foreach ($rows as &$entry) {
        $details = [];
        $missing = [];
        foreach ((array) ($entry['dependencies'] ?? []) as $depSlug) {
            $depSlug = strtolower(trim((string) $depSlug));
            if ($depSlug === '') {
                continue;
            }
            if (!isset($indexBySlug[$depSlug])) {
                $details[] = ['slug' => $depSlug, 'status' => 'missing'];
                $missing[] = $depSlug;
                continue;
            }
            $depModule = $indexBySlug[$depSlug];
            $depEnabled = (bool) ($depModule['enabled'] ?? false);
            if ($depEnabled) {
                $details[] = ['slug' => $depSlug, 'status' => 'ok'];
            } else {
                $details[] = ['slug' => $depSlug, 'status' => 'inactive'];
                $missing[] = $depSlug;
            }
        }
        $entry['dependency_details'] = $details;
        $entry['missing_dependencies'] = $missing;
        $entry['dependency_blocking'] = $missing !== [];
    }
    unset($entry);

    usort($rows, static fn (array $a, array $b): int => strcmp((string) ($a['scope'] . '/' . $a['slug']), (string) ($b['scope'] . '/' . $b['slug'])));

    $stats = [
        'total' => count($rows),
        'active' => count(array_filter($rows, static fn (array $r): bool => (bool) ($r['enabled'] ?? false))),
        'inactive' => count(array_filter($rows, static fn (array $r): bool => !((bool) ($r['enabled'] ?? false)))),
        'errors' => count(array_filter($rows, static fn (array $r): bool => ((array) ($r['errors'] ?? [])) !== [] || (($r['state'] ?? '') === 'error' || ($r['state'] ?? '') === 'invalid' || ($r['state'] ?? '') === 'incompatible'))),
        'trust_alerts' => count(array_filter($rows, static fn (array $r): bool => !((bool) ($r['trusted'] ?? false)))),
        'dependency_alerts' => count(array_filter($rows, static fn (array $r): bool => (bool) ($r['dependency_blocking'] ?? false))),
    ];

    return ['rows' => $rows, 'stats' => $stats];
};

$toggleModuleState = static function (string $scope, string $slug, bool $enabled): array {
    $activator = new CoreModuleActivator();
    return $enabled ? $activator->activate($scope, $slug) : $activator->deactivate($scope, $slug);
};

$resolveModuleDependencies = static function (string $scope, string $slug, bool $activateTarget = true): array {
    $scope = strtolower(trim($scope));
    $slug = strtolower(trim($slug));
    if ($scope === '' || $slug === '') {
        return ['ok' => false, 'message' => 'Paramètres module invalides.'];
    }

    $extractRequires = static function (array $manifest): array {
        $deps = $manifest['dependencies'] ?? [];
        if (!is_array($deps)) {
            return [];
        }
        if (array_is_list($deps)) {
            return array_values(array_unique(array_filter(array_map(static fn ($dep): string => strtolower(trim((string) $dep)), $deps), static fn (string $v): bool => $v !== '')));
        }
        return array_values(array_unique(array_filter(array_map(static fn ($dep): string => strtolower(trim((string) $dep)), (array) ($deps['requires'] ?? [])), static fn (string $v): bool => $v !== '')));
    };

    $findBySlug = static function (array $snapshot, string $targetSlug): ?array {
        foreach ((array) ($snapshot['modules'] ?? []) as $module) {
            $mSlug = strtolower(trim((string) ($module['manifest']['slug'] ?? '')));
            if ($mSlug === $targetSlug) {
                return $module;
            }
        }
        return null;
    };

    $findByScopeSlug = static function (array $snapshot, string $targetScope, string $targetSlug): ?array {
        foreach ((array) ($snapshot['modules'] ?? []) as $module) {
            $mSlug = strtolower(trim((string) ($module['manifest']['slug'] ?? '')));
            $mScope = strtolower(trim((string) ($module['manifest']['type'] ?? '')));
            if ($mSlug === $targetSlug && $mScope === $targetScope) {
                return $module;
            }
        }
        return null;
    };

    $activator = new CoreModuleActivator();
    $market = new CoreMarketEngine();
    $processed = [];
    $messages = [];
    $errors = [];

    $ensureDependency = function (string $depSlug) use (&$ensureDependency, &$processed, &$messages, &$errors, $findBySlug, $extractRequires, $activator, $market): bool {
        if (isset($processed[$depSlug])) {
            return true;
        }

        $snapshot = (new CoreModuleLoader())->scan();
        $depModule = $findBySlug($snapshot, $depSlug);

        if (!is_array($depModule)) {
            $catalog = $market->catalog();
            $items = is_array($catalog['items'] ?? null) ? $catalog['items'] : [];
            $candidate = null;
            $bestScore = -INF;
            foreach ($items as $item) {
                if (!is_array($item)) {
                    continue;
                }
                if (strtolower(trim((string) ($item['slug'] ?? ''))) !== $depSlug) {
                    continue;
                }
                if ((bool) ($item['compatible'] ?? true) !== true) {
                    continue;
                }
                $score = (float) ($item['trust_score'] ?? 0);
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $candidate = $item;
                }
            }
            if (!is_array($candidate)) {
                $errors[] = 'Dépendance introuvable dans le catalogue: ' . $depSlug;
                return false;
            }
            $install = $market->install($candidate);
            if (!(bool) ($install['ok'] ?? false)) {
                $errors[] = 'Échec installation dépendance ' . $depSlug . ': ' . (string) ($install['message'] ?? 'Erreur inconnue');
                return false;
            }
            $messages[] = 'Dépendance installée: ' . $depSlug;
            $snapshot = (new CoreModuleLoader())->scan();
            $depModule = $findBySlug($snapshot, $depSlug);
            if (!is_array($depModule)) {
                $errors[] = 'Dépendance installée mais introuvable après scan: ' . $depSlug;
                return false;
            }
        }

        $depManifest = (array) ($depModule['manifest'] ?? []);
        foreach ($extractRequires($depManifest) as $subDep) {
            if ($subDep === $depSlug) {
                continue;
            }
            if (!$ensureDependency($subDep)) {
                return false;
            }
        }

        if (!((bool) ($depModule['enabled'] ?? false))) {
            $depScope = strtolower(trim((string) ($depManifest['type'] ?? 'admin')));
            $activate = $activator->activate($depScope, $depSlug);
            if (!(bool) ($activate['ok'] ?? false)) {
                $errors[] = 'Échec activation dépendance ' . $depSlug . ': ' . (string) ($activate['message'] ?? 'Erreur inconnue');
                return false;
            }
            $messages[] = 'Dépendance activée: ' . $depSlug;
        }

        $processed[$depSlug] = true;
        return true;
    };

    $snapshot = (new CoreModuleLoader())->scan();
    $targetModule = $findByScopeSlug($snapshot, $scope, $slug);
    if (!is_array($targetModule)) {
        return ['ok' => false, 'message' => 'Module cible introuvable.'];
    }

    $targetManifest = (array) ($targetModule['manifest'] ?? []);
    $required = $extractRequires($targetManifest);
    foreach ($required as $depSlug) {
        if (!$ensureDependency($depSlug)) {
            return ['ok' => false, 'message' => implode(' | ', array_values(array_unique($errors))), 'errors' => array_values(array_unique($errors))];
        }
    }

    if ($activateTarget && !((bool) ($targetModule['enabled'] ?? false))) {
        $activateTargetRes = $activator->activate($scope, $slug);
        if (!(bool) ($activateTargetRes['ok'] ?? false)) {
            $errors[] = 'Échec activation module cible: ' . (string) ($activateTargetRes['message'] ?? 'Erreur inconnue');
            return ['ok' => false, 'message' => implode(' | ', array_values(array_unique($errors))), 'errors' => array_values(array_unique($errors))];
        }
        $messages[] = 'Module activé: ' . $slug;
    }

    if ($messages === []) {
        $messages[] = 'Aucune action requise, dépendances déjà satisfaites.';
    }

    return [
        'ok' => true,
        'message' => implode(' | ', $messages),
        'messages' => $messages,
    ];
};

$coreSettingsDefaults = [
    'general' => [
        'app_name' => (string) env('APP_NAME', 'CATMIN'),
        'app_env' => (string) config('app.env', 'production'),
        'timezone' => (string) config('app.timezone', 'UTC'),
        'admin_path' => (string) config('security.admin_path', 'admin'),
    ],
    'security' => [
        'session_minutes' => 120,
        'max_attempts' => 5,
        'password_min' => 12,
        'enforce_2fa' => false,
    ],
    'email' => [
        'enabled' => false,
        'driver' => 'smtp',
        'from_name' => 'CATMIN',
        'from_email' => 'noreply@example.com',
        'host' => '',
        'port' => 587,
        'encryption' => 'tls',
        'username' => '',
    ],
    'interface' => [
        'theme_default' => 'corporate',
        'compact_sidebar' => true,
        'table_density' => 'comfortable',
        'show_debug' => false,
        'sidebar_order' => '',
    ],
    'maintenance' => [
        'enabled' => false,
        'message' => 'Maintenance en cours',
        'allow_admin' => true,
    ],
    'system' => [
        'cron_enabled' => filter_var((string) env('CRON_ENABLED', '1'), FILTER_VALIDATE_BOOLEAN),
    ],
];

$upsertCoreSetting = static function (\PDO $pdo, string $table, string $category, string $key, string $value, bool $isPublic = false): bool {
    $check = $pdo->prepare('SELECT id FROM ' . $table . ' WHERE category = :category AND setting_key = :setting_key LIMIT 1');
    $check->execute(['category' => $category, 'setting_key' => $key]);
    $id = $check->fetchColumn();

    if ($id !== false) {
        $update = $pdo->prepare('UPDATE ' . $table . ' SET setting_value = :setting_value, is_public = :is_public, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
        return $update->execute([
            'setting_value' => $value,
            'is_public' => $isPublic ? 1 : 0,
            'id' => (int) $id,
        ]);
    }

    $insert = $pdo->prepare('INSERT INTO ' . $table . ' (category, setting_key, setting_value, is_public, updated_at) VALUES (:category, :setting_key, :setting_value, :is_public, CURRENT_TIMESTAMP)');
    return $insert->execute([
        'category' => $category,
        'setting_key' => $key,
        'setting_value' => $value,
        'is_public' => $isPublic ? 1 : 0,
    ]);
};

$loadCoreSettings = static function (\PDO $pdo, string $table) use ($coreSettingsDefaults): array {
    $data = $coreSettingsDefaults;
    $stmt = $pdo->query('SELECT category, setting_key, setting_value FROM ' . $table);
    $rows = $stmt !== false ? ($stmt->fetchAll(\PDO::FETCH_ASSOC) ?: []) : [];
    $rawIndex = [];

    foreach ($rows as $row) {
        $category = (string) ($row['category'] ?? '');
        $key = (string) ($row['setting_key'] ?? '');
        $raw = (string) ($row['setting_value'] ?? '');
        $rawIndex[$category . '.' . $key] = $raw;
        if ($category === '' || $key === '' || !array_key_exists($category, $data) || !array_key_exists($key, $data[$category])) {
            continue;
        }

        $default = $data[$category][$key];
        if (is_bool($default)) {
            $data[$category][$key] = in_array(strtolower($raw), ['1', 'true', 'yes', 'on'], true);
        } elseif (is_int($default)) {
            $data[$category][$key] = (int) $raw;
        } else {
            $data[$category][$key] = $raw;
        }
    }

    $compatValue = static function (array $index, string $alias, mixed $current): mixed {
        if (!array_key_exists($alias, $index)) {
            return $current;
        }
        $raw = (string) $index[$alias];
        if (is_bool($current)) {
            return in_array(strtolower($raw), ['1', 'true', 'yes', 'on'], true);
        }
        if (is_int($current)) {
            return (int) $raw;
        }
        return $raw;
    };

    $data['general']['app_name'] = $compatValue($rawIndex, 'app.name', $data['general']['app_name']);
    $data['general']['timezone'] = $compatValue($rawIndex, 'system.timezone', $data['general']['timezone']);
    $data['general']['admin_path'] = $compatValue($rawIndex, 'security.admin_path', $data['general']['admin_path']);

    return $data;
};

$saveCoreSettings = static function (\PDO $pdo, string $table, array $settings) use ($upsertCoreSetting): bool {
    foreach ($settings as $category => $values) {
        if (!is_array($values)) {
            continue;
        }
        foreach ($values as $key => $value) {
            $stringValue = is_bool($value) ? ($value ? '1' : '0') : (string) $value;
            if (!$upsertCoreSetting($pdo, $table, (string) $category, (string) $key, $stringValue, false)) {
                return false;
            }
        }
    }
    return true;
};

$readCoreSetting = static function (\PDO $pdo, string $table, string $category, string $key, mixed $default = null): mixed {
    $stmt = $pdo->prepare('SELECT setting_value FROM ' . $table . ' WHERE category = :category AND setting_key = :setting_key LIMIT 1');
    $stmt->execute([
        'category' => $category,
        'setting_key' => $key,
    ]);
    $value = $stmt->fetchColumn();
    if ($value === false || $value === null) {
        return $default;
    }

    $raw = (string) $value;
    if (is_bool($default)) {
        return in_array(strtolower($raw), ['1', 'true', 'yes', 'on'], true);
    }
    if (is_int($default)) {
        return (int) $raw;
    }

    return $raw;
};

$appendCoreLog = static function (\PDO $pdo, string $table, string $channel, string $level, string $message, ?array $context = null): void {
    $insert = $pdo->prepare('INSERT INTO ' . $table . ' (channel, level, message, context, created_at) VALUES (:channel, :level, :message, :context, CURRENT_TIMESTAMP)');
    $insert->execute([
        'channel' => $channel,
        'level' => strtoupper($level),
        'message' => $message,
        'context' => $context !== null ? (json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: null) : null,
    ]);
};

$loadSystemLogs = static function (\PDO $pdo, string $table, int $limit = 200): array {
    try {
        $stmt = $pdo->prepare('SELECT created_at, level, message FROM ' . $table . ' WHERE channel IN (\'system\', \'app\', \'cron\', \'backup\') ORDER BY created_at DESC LIMIT :limit');
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        if (is_array($rows) && $rows !== []) {
            return array_map(static fn (array $r): array => [
                'date' => (string) ($r['created_at'] ?? ''),
                'level' => strtoupper((string) ($r['level'] ?? 'INFO')),
                'message' => (string) ($r['message'] ?? ''),
            ], $rows);
        }
    } catch (\Throwable) {
    }

    $files = [CATMIN_STORAGE . '/logs/catmin.log', CATMIN_ROOT . '/logs/catmin.log'];
    foreach ($files as $file) {
        if (!is_file($file)) {
            continue;
        }
        $raw = (array) file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $raw = array_slice($raw, -$limit);
        return array_map(static fn (string $line): array => [
            'date' => date('Y-m-d H:i:s'),
            'level' => 'INFO',
            'message' => $line,
        ], array_reverse($raw));
    }

    return [];
};

$listBackups = static function (\PDO $pdo, string $table): array {
    try {
        $stmt = $pdo->query("SELECT id, backup_type, status, file_path, checksum, size_bytes, created_at FROM " . $table . " WHERE backup_type != 'restore' ORDER BY created_at DESC LIMIT 200");
        $rows = $stmt !== false ? ($stmt->fetchAll(\PDO::FETCH_ASSOC) ?: []) : [];
        return array_map(static fn (array $r): array => [
            'id' => (int) ($r['id'] ?? 0),
            'name' => basename((string) ($r['file_path'] ?? 'backup')),
            'path' => (string) ($r['file_path'] ?? ''),
            'type' => (string) ($r['backup_type'] ?? 'manual'),
            'status' => (string) ($r['status'] ?? ''),
            'size' => (int) ($r['size_bytes'] ?? 0),
            'date' => (string) ($r['created_at'] ?? ''),
            'exists' => is_file((string) ($r['file_path'] ?? '')),
        ], $rows);
    } catch (\Throwable) {
        return [];
    }
};

$loadSystemState = static function (\PDO $pdo, string $settingsTable, string $backupsTable) use ($readCoreSetting): array {
    $lastBackup = (string) $readCoreSetting($pdo, $settingsTable, 'maintenance', 'last_backup', '-');
    if ($lastBackup === '-' || $lastBackup === '') {
        $stmt = $pdo->query('SELECT created_at FROM ' . $backupsTable . ' WHERE backup_type != \'restore\' ORDER BY created_at DESC LIMIT 1');
        $lastBackup = (string) (($stmt !== false ? $stmt->fetchColumn() : null) ?: '-');
    }

    return [
        'maintenance' => (bool) $readCoreSetting($pdo, $settingsTable, 'maintenance', 'enabled', false),
        'maintenance_level' => max(1, min(3, (int) $readCoreSetting($pdo, $settingsTable, 'maintenance', 'level', 1))),
        'maintenance_reason' => (string) $readCoreSetting($pdo, $settingsTable, 'maintenance', 'reason', ''),
        'maintenance_message' => (string) $readCoreSetting($pdo, $settingsTable, 'maintenance', 'message', 'Maintenance en cours'),
        'maintenance_allow_admin' => (bool) $readCoreSetting($pdo, $settingsTable, 'maintenance', 'allow_admin', true),
        'maintenance_allowed_ips' => (string) $readCoreSetting($pdo, $settingsTable, 'maintenance', 'allowed_ips', ''),
        'maintenance_allowed_admin_ids' => (string) $readCoreSetting($pdo, $settingsTable, 'maintenance', 'allowed_admin_ids', ''),
        'maintenance_started_at' => (string) $readCoreSetting($pdo, $settingsTable, 'maintenance', 'started_at', ''),
        'maintenance_enabled_by' => (string) $readCoreSetting($pdo, $settingsTable, 'maintenance', 'enabled_by', ''),
        'last_backup' => $lastBackup,
        'last_restore' => (string) $readCoreSetting($pdo, $settingsTable, 'maintenance', 'last_restore', '-'),
    ];
};

$saveSystemState = static function (\PDO $pdo, string $settingsTable, array $state) use ($upsertCoreSetting): bool {
    $ok = true;
    if (array_key_exists('maintenance', $state)) {
        $ok = $ok && $upsertCoreSetting($pdo, $settingsTable, 'maintenance', 'enabled', ((bool) $state['maintenance']) ? '1' : '0', false);
    }
    if (array_key_exists('maintenance_level', $state)) {
        $ok = $ok && $upsertCoreSetting($pdo, $settingsTable, 'maintenance', 'level', (string) max(1, min(3, (int) $state['maintenance_level'])), false);
    }
    if (array_key_exists('maintenance_reason', $state)) {
        $ok = $ok && $upsertCoreSetting($pdo, $settingsTable, 'maintenance', 'reason', trim((string) $state['maintenance_reason']), false);
    }
    if (array_key_exists('maintenance_message', $state)) {
        $ok = $ok && $upsertCoreSetting($pdo, $settingsTable, 'maintenance', 'message', trim((string) $state['maintenance_message']), false);
    }
    if (array_key_exists('maintenance_allow_admin', $state)) {
        $ok = $ok && $upsertCoreSetting($pdo, $settingsTable, 'maintenance', 'allow_admin', ((bool) $state['maintenance_allow_admin']) ? '1' : '0', false);
    }
    if (array_key_exists('maintenance_allowed_ips', $state)) {
        $ok = $ok && $upsertCoreSetting($pdo, $settingsTable, 'maintenance', 'allowed_ips', trim((string) $state['maintenance_allowed_ips']), false);
    }
    if (array_key_exists('maintenance_allowed_admin_ids', $state)) {
        $ok = $ok && $upsertCoreSetting($pdo, $settingsTable, 'maintenance', 'allowed_admin_ids', trim((string) $state['maintenance_allowed_admin_ids']), false);
    }
    if (array_key_exists('maintenance_started_at', $state)) {
        $ok = $ok && $upsertCoreSetting($pdo, $settingsTable, 'maintenance', 'started_at', trim((string) $state['maintenance_started_at']), false);
    }
    if (array_key_exists('maintenance_enabled_by', $state)) {
        $ok = $ok && $upsertCoreSetting($pdo, $settingsTable, 'maintenance', 'enabled_by', trim((string) $state['maintenance_enabled_by']), false);
    }
    if (array_key_exists('last_backup', $state)) {
        $ok = $ok && $upsertCoreSetting($pdo, $settingsTable, 'maintenance', 'last_backup', (string) $state['last_backup'], false);
    }
    if (array_key_exists('last_restore', $state)) {
        $ok = $ok && $upsertCoreSetting($pdo, $settingsTable, 'maintenance', 'last_restore', (string) $state['last_restore'], false);
    }
    return $ok;
};

$loadCronHistory = static function (\PDO $pdo, string $table, int $limit = 20): array {
    $stmt = $pdo->prepare('SELECT created_at, level, message FROM ' . $table . ' WHERE channel = :channel ORDER BY created_at DESC LIMIT :limit');
    $stmt->bindValue(':channel', 'cron', \PDO::PARAM_STR);
    $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    if (!is_array($rows)) {
        return [];
    }

    return array_map(
        static fn (array $row): string => sprintf(
            '%s | [%s] %s',
            (string) ($row['created_at'] ?? '-'),
            strtoupper((string) ($row['level'] ?? 'INFO')),
            (string) ($row['message'] ?? '')
        ),
        $rows
    );
};

$ensureCronTasksTable = static function (\PDO $pdo, string $table): void {
    $driver = (string) $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
    if ($driver === 'sqlite') {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS ' . $table . ' ('
            . 'id INTEGER PRIMARY KEY AUTOINCREMENT, '
            . 'name TEXT NOT NULL, '
            . 'script_path TEXT NOT NULL, '
            . 'schedule_expr TEXT NOT NULL, '
            . 'is_active INTEGER NOT NULL DEFAULT 1, '
            . 'last_run_at DATETIME NULL, '
            . 'created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, '
            . 'updated_at DATETIME NULL'
            . ')'
        );
        return;
    }

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS ' . $table . ' ('
        . 'id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
        . 'name VARCHAR(191) NOT NULL, '
        . 'script_path VARCHAR(255) NOT NULL, '
        . 'schedule_expr VARCHAR(120) NOT NULL, '
        . 'is_active TINYINT(1) NOT NULL DEFAULT 1, '
        . 'last_run_at DATETIME NULL, '
        . 'created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, '
        . 'updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP'
        . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
};

$ensureCronDirectory = static function (): void {
    $dir = CATMIN_ROOT . '/cron';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
};

$seedDefaultCronTasks = static function (\PDO $pdo, string $table): void {
    $defaults = [
        ['name' => 'Core Cache Cleanup', 'script_path' => 'core/cron/core-cache-cleanup.php', 'schedule_expr' => '0 */6 * * *'],
        ['name' => 'Core Logs Rotate', 'script_path' => 'core/cron/core-logs-rotate.php', 'schedule_expr' => '10 2 * * *'],
        ['name' => 'Core Health Check', 'script_path' => 'core/cron/core-health-check.php', 'schedule_expr' => '*/15 * * * *'],
        ['name' => 'Core Backup', 'script_path' => 'core/cron/core-backup.php', 'schedule_expr' => '30 2 * * *'],
    ];

    foreach ($defaults as $task) {
        $check = $pdo->prepare('SELECT id FROM ' . $table . ' WHERE script_path = :script_path LIMIT 1');
        $check->execute(['script_path' => (string) $task['script_path']]);
        $exists = $check->fetchColumn();
        if ($exists !== false) {
            continue;
        }

        $insert = $pdo->prepare(
            'INSERT INTO ' . $table . ' (name, script_path, schedule_expr, is_active, created_at, updated_at) VALUES (:name, :script_path, :schedule_expr, :is_active, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $insert->execute([
            'name' => (string) $task['name'],
            'script_path' => (string) $task['script_path'],
            'schedule_expr' => (string) $task['schedule_expr'],
            'is_active' => 0,
        ]);
    }
};

$ensureSuperAdminPermissions = static function (\PDO $pdo) use ($rolesTable, $permissionsTable, $rolePermissionsTable): void {
    $roleStmt = $pdo->prepare('SELECT id FROM ' . $rolesTable . ' WHERE slug = :slug LIMIT 1');
    $roleStmt->execute(['slug' => 'super-admin']);
    $roleId = (int) ($roleStmt->fetchColumn() ?: 0);
    if ($roleId <= 0) {
        return;
    }

    $permRows = $pdo->query('SELECT id FROM ' . $permissionsTable . ' ORDER BY id ASC');
    $permissionIds = $permRows !== false ? ($permRows->fetchAll(\PDO::FETCH_COLUMN) ?: []) : [];
    if (!is_array($permissionIds) || $permissionIds === []) {
        return;
    }

    $findPivot = $pdo->prepare('SELECT id FROM ' . $rolePermissionsTable . ' WHERE role_id = :role_id AND permission_id = :permission_id LIMIT 1');
    $insertPivot = $pdo->prepare('INSERT INTO ' . $rolePermissionsTable . ' (role_id, permission_id) VALUES (:role_id, :permission_id)');
    foreach ($permissionIds as $permissionIdRaw) {
        $permissionId = (int) $permissionIdRaw;
        if ($permissionId <= 0) {
            continue;
        }
        $findPivot->execute(['role_id' => $roleId, 'permission_id' => $permissionId]);
        if ($findPivot->fetchColumn() !== false) {
            continue;
        }
        $insertPivot->execute(['role_id' => $roleId, 'permission_id' => $permissionId]);
    }
};

$buildSqlDump = static function (\PDO $pdo): string {
    $driver = (string) $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
    $lines = [
        '-- CATMIN SQL dump',
        '-- Generated: ' . date('c'),
        '-- Driver: ' . $driver,
        '',
    ];

    $quoteIdent = static function (string $identifier) use ($driver): string {
        if ($driver === 'pgsql') {
            return '"' . str_replace('"', '""', $identifier) . '"';
        }
        return '`' . str_replace('`', '``', $identifier) . '`';
    };

    $exportRows = static function (string $tableName) use ($pdo, $quoteIdent, &$lines): void {
        $tableIdent = $quoteIdent($tableName);
        $rowsStmt = $pdo->query('SELECT * FROM ' . $tableIdent);
        $rows = $rowsStmt !== false ? ($rowsStmt->fetchAll(\PDO::FETCH_ASSOC) ?: []) : [];
        if (!is_array($rows) || $rows === []) {
            return;
        }
        foreach ($rows as $row) {
            $columns = [];
            $values = [];
            foreach ($row as $col => $value) {
                $columns[] = $quoteIdent((string) $col);
                if ($value === null) {
                    $values[] = 'NULL';
                } elseif (is_bool($value)) {
                    $values[] = $value ? '1' : '0';
                } elseif (is_int($value) || is_float($value) || (is_string($value) && preg_match('/^-?[0-9]+(?:\.[0-9]+)?$/', $value) === 1)) {
                    $values[] = (string) $value;
                } else {
                    $values[] = $pdo->quote((string) $value);
                }
            }
            $lines[] = 'INSERT INTO ' . $tableIdent . ' (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ');';
        }
        $lines[] = '';
    };

    if ($driver === 'sqlite') {
        $tablesStmt = $pdo->query("SELECT name, sql FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name ASC");
        $tables = $tablesStmt !== false ? ($tablesStmt->fetchAll(\PDO::FETCH_ASSOC) ?: []) : [];
        foreach ($tables as $table) {
            $name = (string) ($table['name'] ?? '');
            $sql = trim((string) ($table['sql'] ?? ''));
            if ($name === '' || $sql === '') {
                continue;
            }
            $lines[] = $sql . ';';
            $lines[] = '';
            $exportRows($name);
        }
    } else {
        $tables = [];
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $tablesStmt = $pdo->query('SHOW TABLES');
            $tables = $tablesStmt !== false ? ($tablesStmt->fetchAll(\PDO::FETCH_COLUMN) ?: []) : [];
            foreach ($tables as $tableNameRaw) {
                $tableName = (string) $tableNameRaw;
                if ($tableName === '') {
                    continue;
                }
                $createStmt = $pdo->query('SHOW CREATE TABLE ' . $quoteIdent($tableName));
                $createRow = $createStmt !== false ? $createStmt->fetch(\PDO::FETCH_ASSOC) : false;
                if (is_array($createRow)) {
                    $createSql = '';
                    foreach ($createRow as $k => $v) {
                        if (is_string($k) && stripos($k, 'create table') !== false) {
                            $createSql = (string) $v;
                            break;
                        }
                    }
                    if ($createSql !== '') {
                        $lines[] = $createSql . ';';
                        $lines[] = '';
                    }
                }
                $exportRows($tableName);
            }
        }
    }

    return implode(PHP_EOL, $lines) . PHP_EOL;
};

return [
    [
        'method' => 'GET',
        'path' => '/login',
        'handler' => static fn (): Response => (new AuthController())->showLogin(),
    ],
    [
        'method' => 'POST',
        'path' => '/login',
        'handler' => static fn (Request $request): Response => (new AuthController())->login($request),
        'middleware' => [$csrfCheck],
    ],
    [
        'method' => 'GET',
        'path' => '/logout',
        'handler' => static fn (): Response => (new AuthController())->logout(),
        'middleware' => [$authRequired],
    ],
    [
        'method' => 'GET',
        'path' => '/reauth',
        'handler' => static fn (): Response => (new AuthController())->showReauth(),
        'middleware' => [$authRequired],
    ],
    [
        'method' => 'POST',
        'path' => '/reauth',
        'handler' => static fn (Request $request): Response => (new AuthController())->reauth($request),
        'middleware' => [$authRequired, $csrfCheck],
    ],
    [
        'method' => 'GET',
        'path' => '/locked',
        'handler' => static fn (): Response => (new AuthController())->showLocked(),
    ],
    [
        'method' => 'GET',
        'path' => '/password/request',
        'handler' => static fn (): Response => (new AuthController())->showPasswordRequest(),
    ],
    [
        'method' => 'POST',
        'path' => '/password/request',
        'handler' => static fn (Request $request): Response => (new AuthController())->passwordRequest($request),
        'middleware' => [$csrfCheck],
    ],
    [
        'method' => 'GET',
        'path' => '/password/reset',
        'handler' => static fn (): Response => (new AuthController())->showPasswordReset(),
    ],
    [
        'method' => 'POST',
        'path' => '/password/reset',
        'handler' => static fn (Request $request): Response => (new AuthController())->passwordReset($request),
        'middleware' => [$csrfCheck],
    ],
    [
        'method' => 'GET',
        'path' => '/password/change',
        'handler' => static fn (): Response => (new AuthController())->showPasswordChange(),
        'middleware' => [$authRequired],
    ],
    [
        'method' => 'POST',
        'path' => '/password/change',
        'handler' => static fn (Request $request): Response => (new AuthController())->passwordChange($request),
        'middleware' => [$authRequired, $csrfCheck, $recentPassword],
    ],

    [
        'method' => 'GET',
        'path' => '/',
        'handler' => static function () use (
            $resolveGitMeta,
            $scanModules,
            $readCoreSetting,
            $coreSettingsTable,
            $coreBackupsTable
        ): Response {
            $controller = new AuthController();
            $user = $controller->currentUser();
            $adminBase = $controller->adminBasePath();
            $pdo = (new ConnectionManager())->connection();

            $dbDriver = (string) $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
            $dbVersion = (string) $pdo->getAttribute(\PDO::ATTR_SERVER_VERSION);
            if ($dbDriver === 'sqlite') {
                $stmt = $pdo->query('SELECT sqlite_version()');
                $dbVersion = (string) (($stmt !== false ? $stmt->fetchColumn() : null) ?: $dbVersion);
            }

            $cronEnabled = (bool) $readCoreSetting(
                $pdo,
                $coreSettingsTable,
                'system',
                'cron_enabled',
                filter_var((string) env('CRON_ENABLED', '1'), FILTER_VALIDATE_BOOLEAN)
            );
            $maintenanceMode = (bool) $readCoreSetting($pdo, $coreSettingsTable, 'maintenance', 'enabled', false);
            $gitMeta = $resolveGitMeta();
            $modulesStats = (array) (($scanModules()['stats'] ?? []));
            $lastBackup = '-';
            $lastBackupHint = 'A configurer';
            $lastBackupStmt = $pdo->query('SELECT file_path, created_at FROM ' . $coreBackupsTable . ' WHERE backup_type != \'restore\' ORDER BY created_at DESC LIMIT 1');
            $lastBackupRow = $lastBackupStmt !== false ? $lastBackupStmt->fetch(\PDO::FETCH_ASSOC) : null;
            if (is_array($lastBackupRow)) {
                $lastBackup = basename((string) ($lastBackupRow['file_path'] ?? 'backup'));
                $lastBackupHint = (string) ($lastBackupRow['created_at'] ?? '-');
            } else {
                $savedLastBackup = (string) $readCoreSetting($pdo, $coreSettingsTable, 'maintenance', 'last_backup', '');
                if ($savedLastBackup !== '') {
                    $lastBackupHint = $savedLastBackup;
                }
            }

            $healthSnapshot = (new HealthCheckService())->run();
            $monitoringSnapshot = (new MonitoringService())->snapshot();
            $updatesSnapshot = (new CoreUpdateCenter())->buildSnapshot();
            $securityAlerts = (int) (($monitoringSnapshot['widgets']['security_alerts']['count'] ?? 0));
            $criticalErrors = (int) (($monitoringSnapshot['widgets']['critical_errors']['count'] ?? 0));

            $modulesErrors = (int) ($modulesStats['errors'] ?? 0);
            $stats = [
                ['title' => __('dashboard.stats.admins'), 'value' => $user !== null ? '1' : '0', 'hint' => __('dashboard.stats.account_active'), 'tone' => 'success', 'icon' => 'bi-people'],
                ['title' => __('dashboard.stats.modules'), 'value' => (string) ((int) ($modulesStats['active'] ?? 0)), 'hint' => $modulesErrors > 0 ? str_replace(':count', (string) $modulesErrors, __('dashboard.stats.modules_errors')) : __('dashboard.stats.modules_ok'), 'tone' => $modulesErrors > 0 ? 'warning' : 'success', 'icon' => 'bi-puzzle'],
                ['title' => __('dashboard.stats.security_alerts'), 'value' => (string) $securityAlerts, 'hint' => $securityAlerts > 0 ? __('dashboard.stats.security_check') : __('dashboard.stats.no_alert'), 'tone' => $securityAlerts > 0 ? 'warning' : 'success', 'icon' => 'bi-shield-check'],
                ['title' => 'Dernier backup', 'value' => $lastBackup, 'hint' => $lastBackupHint, 'tone' => $lastBackup === '-' ? 'info' : 'success', 'icon' => 'bi-database-check'],
            ];

            $installLockPresent = is_file(CATMIN_STORAGE . '/install.lock') || is_file(CATMIN_STORAGE . '/install/installed.lock');
            $activity = [
                ['title' => __('dashboard.activity.admin_login'), 'meta' => __('dashboard.activity.session_active'), 'status' => 'OK', 'variant' => 'success'],
                ['title' => __('dashboard.activity.admin_route'), 'meta' => $adminBase, 'status' => 'INFO', 'variant' => 'info'],
                ['title' => __('dashboard.activity.installer_lock'), 'meta' => $installLockPresent ? __('common.present') : __('common.absent'), 'status' => $installLockPresent ? 'LOCK' : 'WARN', 'variant' => $installLockPresent ? 'success' : 'warning'],
            ];

            $health = array_map(static function (array $line): array {
                $variant = match ((string) ($line['status'] ?? 'unknown')) {
                    'healthy' => 'success',
                    'warning' => 'warning',
                    'critical' => 'danger',
                    default => 'neutral',
                };
                return [
                    'label' => (string) ($line['label'] ?? '-'),
                    'value' => (string) ($line['detail'] ?? '-'),
                    'variant' => $variant,
                ];
            }, array_slice((array) ($healthSnapshot['checks'] ?? []), 0, 6));

            $events = [
                ['label' => 'Core boot', 'status' => 'OK', 'date' => date('Y-m-d H:i')],
                ['label' => 'Routing engine', 'status' => 'OK', 'date' => date('Y-m-d H:i')],
                ['label' => 'Admin layout shell', 'status' => 'OK', 'date' => date('Y-m-d H:i')],
                ['label' => 'Monitoring errors', 'status' => $criticalErrors > 0 ? 'WARN' : 'OK', 'date' => date('Y-m-d H:i')],
            ];

            $versionInfo = [
                'version' => Version::current(),
                'php' => PHP_VERSION,
                'db_driver' => $dbDriver,
                'db_version' => $dbVersion,
                'cron_status' => $cronEnabled ? 'Actif' : 'Inactif',
                'maintenance' => $maintenanceMode ? 'Active' : 'Inactive',
                'admin_path' => $adminBase,
                'app_env' => (string) config('app.env', 'production'),
                'git_branch' => (string) ($gitMeta['branch'] ?? '-'),
                'git_commit' => (string) ($gitMeta['commit'] ?? '-'),
            ];

            return View::make('dashboard.index', [
                'user' => $user,
                'adminBase' => $adminBase,
                'stats' => $stats,
                'activity' => $activity,
                'health' => $health,
                'events' => $events,
                'versionInfo' => $versionInfo,
                'monitoring' => $monitoringSnapshot,
                'updatesSnapshot' => $updatesSnapshot,
            ], 'admin');
        },
        'middleware' => [$authRequired],
    ],

    [
        'method' => 'GET',
        'path' => '/modules',
        'handler' => static function (Request $request) use ($scanModules, $consumeFlash): Response {
            $controller = new AuthController();
            $adminBase = $controller->adminBasePath();
            $flash = $consumeFlash();

            $scan = $scanModules();
            $rows = (array) ($scan['rows'] ?? []);
            $stats = (array) ($scan['stats'] ?? []);

            $search = strtolower(trim((string) $request->input('q', '')));
            $status = strtolower(trim((string) $request->input('status', 'all')));
            $scope = strtolower(trim((string) $request->input('scope', 'all')));

            $scopes = array_values(array_unique(array_map(static fn (array $r): string => (string) ($r['scope'] ?? ''), $rows)));
            sort($scopes);

            $filtered = array_values(array_filter($rows, static function (array $row) use ($search, $status, $scope): bool {
                $errors = (array) ($row['errors'] ?? []);
                $enabled = (bool) ($row['enabled'] ?? false);
                $deps = implode(' ', (array) ($row['dependencies'] ?? []));
                $haystack = strtolower(trim((string) (($row['name'] ?? '') . ' ' . ($row['slug'] ?? '') . ' ' . ($row['version'] ?? '') . ' ' . $deps)));

                if ($search !== '' && !str_contains($haystack, $search)) {
                    return false;
                }

                if ($scope !== 'all' && (string) ($row['scope'] ?? '') !== $scope) {
                    return false;
                }

                return match ($status) {
                    'active' => $enabled,
                    'inactive' => !$enabled,
                    'error' => $errors !== [],
                    'issues' => $errors !== [] || !$enabled,
                    default => true,
                };
            }));

            return View::make('modules.index', [
                'adminBase' => $adminBase,
                'rows' => $filtered,
                'stats' => $stats,
                'scopes' => $scopes,
                'filters' => [
                    'q' => $search,
                    'status' => $status,
                    'scope' => $scope,
                ],
                'activeView' => 'manager',
                'message' => (string) ($flash['message'] ?? ''),
                'messageType' => (string) ($flash['type'] ?? 'success'),
            ], 'admin');
        },
        'middleware' => [$authRequired],
    ],

    [
        'method' => 'GET',
        'path' => '/modules/status',
        'handler' => static function (Request $request) use ($scanModules, $consumeFlash): Response {
            $controller = new AuthController();
            $adminBase = $controller->adminBasePath();
            $flash = $consumeFlash();

            $scan = $scanModules();
            $rows = (array) ($scan['rows'] ?? []);
            $stats = (array) ($scan['stats'] ?? []);

            $search = strtolower(trim((string) $request->input('q', '')));
            $status = strtolower(trim((string) $request->input('status', 'all')));
            $scope = strtolower(trim((string) $request->input('scope', 'all')));

            $scopes = array_values(array_unique(array_map(static fn (array $r): string => (string) ($r['scope'] ?? ''), $rows)));
            sort($scopes);

            $filtered = array_values(array_filter($rows, static function (array $row) use ($search, $status, $scope): bool {
                $errors = (array) ($row['errors'] ?? []);
                $enabled = (bool) ($row['enabled'] ?? false);
                $deps = implode(' ', (array) ($row['dependencies'] ?? []));
                $haystack = strtolower(trim((string) (($row['name'] ?? '') . ' ' . ($row['slug'] ?? '') . ' ' . ($row['version'] ?? '') . ' ' . $deps)));

                if ($search !== '' && !str_contains($haystack, $search)) {
                    return false;
                }

                if ($scope !== 'all' && (string) ($row['scope'] ?? '') !== $scope) {
                    return false;
                }

                return match ($status) {
                    'active' => $enabled,
                    'inactive' => !$enabled,
                    'error' => $errors !== [],
                    'issues' => $errors !== [] || !$enabled,
                    default => true,
                };
            }));

            return View::make('modules.index', [
                'adminBase' => $adminBase,
                'rows' => $filtered,
                'stats' => $stats,
                'scopes' => $scopes,
                'filters' => [
                    'q' => $search,
                    'status' => $status,
                    'scope' => $scope,
                ],
                'activeView' => 'status',
                'message' => (string) ($flash['message'] ?? ''),
                'messageType' => (string) ($flash['type'] ?? 'success'),
            ], 'admin');
        },
        'middleware' => [$authRequired],
    ],

    [
        'method' => 'GET',
        'path' => '/modules/market',
        'handler' => static function (Request $request) use ($consumeFlash): Response {
            $controller = new AuthController();
            $adminBase = $controller->adminBasePath();
            $flash = $consumeFlash();

            $engine = new CoreMarketEngine();
            $catalog = $engine->catalog();

            return View::make('modules-market', [
                'adminBase' => $adminBase,
                'catalog' => $catalog,
                'filters' => [
                    'q' => (string) $request->input('q', ''),
                    'status' => (string) $request->input('status', 'all'),
                    'scope' => (string) $request->input('scope', 'all'),
                    'trust' => (string) $request->input('trust', ''),
                ],
                'message' => (string) ($flash['message'] ?? ''),
                'messageType' => (string) ($flash['type'] ?? 'success'),
            ], 'admin');
        },
        'middleware' => [$authRequired],
    ],

    [
        'method' => 'GET',
        'path' => '/locale/{locale}',
        'where' => ['locale' => 'fr|en'],
        'handler' => static function (Request $request, string $locale) use ($redirect): Response {
            if (session_status() !== PHP_SESSION_ACTIVE) {
                @session_start();
            }
            $locale = in_array($locale, ['fr', 'en'], true) ? $locale : 'fr';
            (new CoreI18nEngine())->setLocale($locale);

            $controller = new AuthController();
            $adminBase = $controller->adminBasePath();
            $target = trim((string) ($request->input('next', '')));
            if ($target === '' || !str_starts_with($target, $adminBase)) {
                $target = $adminBase . '/';
            }

            return Response::html('', 302, ['Location' => $target]);
        },
        'middleware' => [$authRequired],
    ],

    [
        'method' => 'GET',
        'path' => '/notifications',
        'handler' => static function () use ($notificationsRepository): Response {
            $controller = new AuthController();
            $adminBase = $controller->adminBasePath();
            $rows = $notificationsRepository->listAll(250);

            return View::make('notifications.index', [
                'adminBase' => $adminBase,
                'rows' => $rows,
            ], 'admin');
        },
        'middleware' => [$authRequired],
    ],

    [
        'method' => 'GET',
        'path' => '/notifications/read/{id}',
        'where' => ['id' => '[0-9]+'],
        'handler' => static function (Request $request, string $id) use ($notificationsRepository): Response {
            $controller = new AuthController();
            $adminBase = $controller->adminBasePath();
            $notificationId = (int) $id;
            if ($notificationId > 0) {
                $notificationsRepository->markRead($notificationId);
            }

            $next = trim((string) $request->input('next', ''));
            if ($next !== '' && str_starts_with($next, $adminBase)) {
                return Response::html('', 302, ['Location' => $next]);
            }

            return Response::html('', 302, ['Location' => $adminBase . '/notifications']);
        },
        'middleware' => [$authRequired],
    ],

    [
        'method' => 'GET',
        'path' => '/notifications/mark-all-read',
        'handler' => static function (Request $request) use ($notificationsRepository): Response {
            $controller = new AuthController();
            $adminBase = $controller->adminBasePath();
            $notificationsRepository->markAllRead();
            $next = trim((string) $request->input('next', ''));
            if ($next !== '' && str_starts_with($next, $adminBase)) {
                return Response::html('', 302, ['Location' => $next]);
            }

            return Response::html('', 302, ['Location' => $adminBase . '/notifications']);
        },
        'middleware' => [$authRequired],
    ],

    [
        'method' => 'GET',
        'path' => '/settings',
        'handler' => static function (Request $request) use ($redirect): Response {
            $controller = new AuthController();
            $adminBase = $controller->adminBasePath();
            $section = strtolower(trim((string) $request->input('section', 'general')));
            $section = match ($section) {
                'mail' => 'mail',
                'security' => 'security',
                'apps', 'module-repositories', 'market' => 'advanced',
                'appearance', 'sidebar', 'performance', 'advanced' => $section,
                default => 'general',
            };
            return $redirect($adminBase . '/settings/' . $section, [
                'msg' => (string) $request->input('msg', ''),
                'mt' => (string) $request->input('mt', 'success'),
            ]);
        },
        'middleware' => [$authRequired],
    ],

    [
        'method' => 'GET',
        'path' => '/settings/{section}',
        'where' => ['section' => 'general|appearance|sidebar|mail|performance|security|advanced|apps|module-repositories'],
        'handler' => static function (Request $request, string $section) use ($consumeFlash, $appsRepository): Response {
            $controller = new AuthController();
            $adminBase = $controller->adminBasePath();
            $engine = new CoreSettingsEngine();
            $flash = $consumeFlash();
            $section = strtolower(trim($section));
            $registry = new CoreModuleRepositoryRegistry();

            if (in_array($section, ['apps', 'module-repositories'], true)) {
                $redirectTo = 'advanced';
                return Response::html('', 302, ['Location' => $adminBase . '/settings/' . $redirectTo]);
            }

            $settings = $engine->all();
            foreach (['general', 'security', 'mail', 'ui', 'maintenance', 'backup', 'system'] as $key) {
                if (!isset($settings[$key]) || !is_array($settings[$key])) {
                    $settings[$key] = [];
                }
            }
            $settings['interface'] = $settings['ui'];
            $settings['email'] = $settings['mail'];

            $sidebarGroupMeta = [
                'dashboard' => ['label' => __('nav.dashboard'), 'icon' => 'house-door', 'order' => 10],
                'organization' => ['label' => __('nav.organization'), 'icon' => 'diagram-3', 'order' => 40],
                'system' => ['label' => __('nav.system'), 'icon' => 'speedometer2', 'order' => 60],
                'modules' => ['label' => __('nav.modules'), 'icon' => 'puzzle', 'order' => 70],
                'features' => ['label' => __('nav.features'), 'icon' => 'sparkles', 'order' => 75],
                'settings' => ['label' => __('nav.settings'), 'icon' => 'gear', 'order' => 80],
                'content' => ['label' => __('nav.content'), 'icon' => 'file-earmark-text', 'order' => 20],
                'media' => ['label' => __('nav.media'), 'icon' => 'images', 'order' => 30],
                'marketing' => ['label' => __('nav.marketing'), 'icon' => 'megaphone', 'order' => 50],
            ];

            $sidebarOrderRaw = (string) (($settings['ui']['sidebar_order'] ?? '') ?: '');
            $sidebarOrder = array_values(array_filter(array_map('trim', explode(',', $sidebarOrderRaw)), static fn (string $value): bool => $value !== ''));
            $sidebarItemOrderRaw = (string) (($settings['ui']['sidebar_item_order'] ?? '') ?: '');
            $sidebarItemOrder = array_values(array_filter(array_map('trim', explode(',', $sidebarItemOrderRaw)), static fn (string $value): bool => $value !== ''));
            $sidebarOrderIds = (array) (($settings['ui']['sidebar_order_ids'] ?? []) ?: []);
            $sidebarLocale = function_exists('catmin_locale')
                ? strtolower((string) catmin_locale())
                : strtolower((string) config('app.locale', 'fr'));
            if (!in_array($sidebarLocale, ['fr', 'en'], true)) {
                $sidebarLocale = 'fr';
            }

            $resolveSidebarLabel = static function (array $item, string $fallback, string $locale): string {
                $labelI18n = $item['label_i18n'] ?? null;
                if (is_array($labelI18n)) {
                    $localized = trim((string) ($labelI18n[$locale] ?? ''));
                    if ($localized !== '') {
                        return $localized;
                    }
                }

                $localizedKey = 'label_' . $locale;
                $localized = trim((string) ($item[$localizedKey] ?? ''));
                if ($localized !== '') {
                    return $localized;
                }

                $label = trim((string) ($item['label'] ?? ''));
                return $label !== '' ? $label : $fallback;
            };

            $loader = new CoreModuleLoader();
            $snapshot = $loader->scan();

            $sidebarEntries = [
                ['key' => 'organization.staff', 'group' => 'organization', 'label' => __('nav.staff_admins'), 'source' => 'core', 'order' => 10],
                ['key' => 'organization.roles', 'group' => 'organization', 'label' => __('nav.roles_permissions'), 'source' => 'core', 'order' => 20],
                ['key' => 'system.monitoring', 'group' => 'system', 'label' => __('nav.monitoring'), 'source' => 'core', 'order' => 10],
                ['key' => 'system.health', 'group' => 'system', 'label' => __('nav.health_check'), 'source' => 'core', 'order' => 20],
                ['key' => 'system.core-update', 'group' => 'system', 'label' => __('nav.core_update'), 'source' => 'core', 'order' => 30],
                ['key' => 'system.queue', 'group' => 'system', 'label' => __('nav.queue'), 'source' => 'core', 'order' => 40],
                ['key' => 'system.logs', 'group' => 'system', 'label' => __('nav.logs'), 'source' => 'core', 'order' => 50],
                ['key' => 'system.notifications', 'group' => 'system', 'label' => __('nav.notifications'), 'source' => 'core', 'order' => 60],
                ['key' => 'system.cron', 'group' => 'system', 'label' => __('nav.cron'), 'source' => 'core', 'order' => 70],
                ['key' => 'system.maintenance', 'group' => 'system', 'label' => __('nav.maintenance'), 'source' => 'core', 'order' => 80],
                ['key' => 'modules.module-manager', 'group' => 'modules', 'label' => __('nav.module_manager'), 'source' => 'core', 'order' => 10],
                ['key' => 'modules.module-status', 'group' => 'modules', 'label' => __('nav.module_status'), 'source' => 'core', 'order' => 20],
                ['key' => 'modules.module-market', 'group' => 'modules', 'label' => __('nav.module_market'), 'source' => 'core', 'order' => 30],
                ['key' => 'modules.trust-center', 'group' => 'modules', 'label' => __('nav.trust_center'), 'source' => 'core', 'order' => 40],
            ];

            $moduleEntrySeen = [];
            foreach ((array) ($snapshot['modules'] ?? []) as $module) {
                // Show entries from all discovered modules so the user can
                // configure navigation even before (re)activation/validation.
                if (!is_array($module)) {
                    continue;
                }
                $manifestPath = (string) ($module['manifest_path'] ?? '');
                $manifest = [];
                if ($manifestPath !== '' && is_file($manifestPath)) {
                    $rawManifest = file_get_contents($manifestPath);
                    $decodedManifest = is_string($rawManifest) ? json_decode($rawManifest, true) : null;
                    if (is_array($decodedManifest)) {
                        $manifest = $decodedManifest;
                    }
                }
                if ($manifest === []) {
                    $manifest = (array) ($module['manifest'] ?? []);
                }
                $items = $manifest['admin_sidebar'] ?? $manifest['sidebar'] ?? $manifest['sidebar_entries'] ?? [];
                if (!is_array($items)) {
                    continue;
                }
                $isActive = (bool) ($module['enabled'] ?? false);
                foreach ($items as $item) {
                    if (!is_array($item)) {
                        continue;
                    }
                    $groupKey = strtolower(trim((string) ($item['group'] ?? 'modules')));
                    $entryKey = strtolower(trim((string) ($item['key'] ?? '')));
                    if ($groupKey === '' || $entryKey === '') {
                        continue;
                    }
                    $compound = $groupKey . '.' . $entryKey;
                    if (isset($moduleEntrySeen[$compound])) {
                        continue;
                    }
                    $moduleEntrySeen[$compound] = true;
                    $sidebarEntries[] = [
                        'key' => $compound,
                        'group' => $groupKey,
                        'label' => $resolveSidebarLabel($item, $entryKey, $sidebarLocale),
                        'source' => 'module',
                        'active' => $isActive,
                        'order' => (int) ($item['order'] ?? 100),
                    ];
                }
            }

            $coreSidebarGroups = ['dashboard', 'organization', 'system', 'modules', 'settings'];
            $sidebarGroups = [];
            foreach ($coreSidebarGroups as $key) {
                $meta = $sidebarGroupMeta[$key] ?? [];
                $sidebarGroups[$key] = [
                    'key' => $key,
                    'label' => (string) ($meta['label'] ?? ucfirst($key)),
                    'icon' => (string) ($meta['icon'] ?? 'chat'),
                    'order' => (int) ($meta['order'] ?? 99),
                    'source' => 'core',
                ];
            }

            foreach ((array) ($snapshot['modules'] ?? []) as $module) {
                if (!((bool) ($module['enabled'] ?? false))) {
                    continue;
                }
                $manifestPath = (string) ($module['manifest_path'] ?? '');
                $manifest = [];
                if ($manifestPath !== '' && is_file($manifestPath)) {
                    $rawManifest = file_get_contents($manifestPath);
                    $decodedManifest = is_string($rawManifest) ? json_decode($rawManifest, true) : null;
                    if (is_array($decodedManifest)) {
                        $manifest = $decodedManifest;
                    }
                }
                if ($manifest === []) {
                    $manifest = (array) ($module['manifest'] ?? []);
                }
                $items = $manifest['admin_sidebar'] ?? $manifest['sidebar'] ?? $manifest['sidebar_entries'] ?? [];
                if (!is_array($items)) {
                    continue;
                }
                foreach ($items as $item) {
                    if (!is_array($item)) {
                        continue;
                    }
                    $groupKey = strtolower(trim((string) ($item['group'] ?? 'modules')));
                    if ($groupKey === '') {
                        continue;
                    }
                    if (isset($sidebarGroups[$groupKey])) {
                        continue;
                    }
                    $meta = $sidebarGroupMeta[$groupKey] ?? null;
                    $sidebarGroups[$groupKey] = [
                        'key' => $groupKey,
                        'label' => $meta !== null ? (string) ($meta['label'] ?? ucfirst($groupKey)) : ucfirst($groupKey),
                        'icon' => $meta !== null ? (string) ($meta['icon'] ?? 'chat') : 'chat',
                        'order' => (int) ($meta['order'] ?? 99),
                        'source' => 'module',
                    ];
                }
            }

            // Preserve groups already saved in order settings, even if module is currently disabled.
            foreach ($sidebarOrder as $groupKey) {
                if ($groupKey === '' || isset($sidebarGroups[$groupKey])) {
                    continue;
                }
                $meta = $sidebarGroupMeta[$groupKey] ?? null;
                $sidebarGroups[$groupKey] = [
                    'key' => $groupKey,
                    'label' => $meta !== null ? (string) ($meta['label'] ?? ucfirst($groupKey)) : ucfirst($groupKey),
                    'icon' => $meta !== null ? (string) ($meta['icon'] ?? 'chat') : 'chat',
                    'order' => (int) ($meta['order'] ?? 99),
                    'source' => 'module',
                ];
            }

            return View::make('settings.index', [
                'adminBase' => $adminBase,
                'settings' => $settings,
                'apps' => $appsRepository->listAll(),
                'repositories' => $registry->listRepositories(),
                'policy' => $registry->policy(),
                'sidebarGroups' => array_values($sidebarGroups),
                'sidebarOrder' => $sidebarOrder,
                'sidebarOrderIds' => $sidebarOrderIds,
                'sidebarEntries' => array_values($sidebarEntries),
                'sidebarItemOrder' => $sidebarItemOrder,
                'section' => $section,
                'message' => (string) ($flash['message'] ?? ''),
                'messageType' => (string) ($flash['type'] ?? 'success'),
                'activeSettingsNav' => 'settings',
            ], 'admin');
        },
        'middleware' => [$authRequired],
    ],

    [
        'method' => 'POST',
        'path' => '/settings',
        'handler' => static function (Request $request) use ($upsertCoreSetting, $redirect, $coreSettingsTable, $appendCoreLog, $coreLogsTable): Response {
            $controller = new AuthController();
            $adminBase = $controller->adminBasePath();
            $pdo = (new ConnectionManager())->connection();
            $engine = new CoreSettingsEngine();

            $data = $engine->all();
            $data['general'] = is_array($data['general'] ?? null) ? $data['general'] : [];
            $data['security'] = is_array($data['security'] ?? null) ? $data['security'] : [];
            $data['mail'] = is_array($data['mail'] ?? null) ? $data['mail'] : [];
            $data['ui'] = is_array($data['ui'] ?? null) ? $data['ui'] : [];
            $data['maintenance'] = is_array($data['maintenance'] ?? null) ? $data['maintenance'] : [];
            $post = $request->post();
            $section = strtolower(trim((string) ($post['section'] ?? 'general')));
            $rawSection = $section;
            $section = match ($section) {
                'mail' => 'mail',
                'security' => 'security',
                'appearance' => 'appearance',
                'sidebar' => 'sidebar',
                'performance' => 'performance',
                'advanced', 'apps', 'module-repositories', 'market' => 'advanced',
                default => 'general',
            };
            $postedTimezone = trim((string) ($post['timezone'] ?? ($data['general']['timezone'] ?? 'UTC')));
            if (!in_array($postedTimezone, \DateTimeZone::listIdentifiers(), true)) {
                $postedTimezone = (string) ($data['general']['timezone'] ?? 'UTC');
            }

            $data['general'] = [
                'app_name' => trim((string) ($post['app_name'] ?? ($data['general']['app_name'] ?? 'CATMIN'))),
                'app_env' => trim((string) ($post['app_env'] ?? ($data['general']['app_env'] ?? 'production'))),
                'timezone' => $postedTimezone,
                'admin_path' => trim((string) ($post['admin_path'] ?? ($data['general']['admin_path'] ?? 'admin'))),
            ];
            $data['security'] = [
                'session_minutes' => max(15, (int) ($post['session_minutes'] ?? ($data['security']['session_minutes'] ?? 120))),
                'max_attempts' => max(3, (int) ($post['max_attempts'] ?? ($data['security']['max_attempts'] ?? 5))),
                'password_min' => max(8, (int) ($post['password_min'] ?? ($data['security']['password_min'] ?? 12))),
                'enforce_2fa' => ((string) ($post['enforce_2fa'] ?? '0')) === '1',
            ];
            $mailSource = is_array($data['mail'] ?? null) ? $data['mail'] : (is_array($data['email'] ?? null) ? $data['email'] : []);
            $data['mail'] = [
                'enabled' => ((string) ($post['email_enabled'] ?? '0')) === '1',
                'driver' => trim((string) ($post['email_driver'] ?? ($mailSource['driver'] ?? 'smtp'))),
                'from_name' => trim((string) ($post['email_from_name'] ?? ($mailSource['from_name'] ?? 'CATMIN'))),
                'from_email' => trim((string) ($post['email_from_email'] ?? ($mailSource['from_email'] ?? 'noreply@example.com'))),
                'host' => trim((string) ($post['email_host'] ?? ($mailSource['host'] ?? ''))),
                'port' => max(1, (int) ($post['email_port'] ?? ($mailSource['port'] ?? 587))),
                'encryption' => trim((string) ($post['email_encryption'] ?? ($mailSource['encryption'] ?? 'tls'))),
                'username' => trim((string) ($post['email_username'] ?? ($mailSource['username'] ?? ''))),
            ];
            $postedSidebarOrderIds = is_array($post['sidebar_order_ids'] ?? null) ? (array) $post['sidebar_order_ids'] : [];
            $normalizedSidebarOrderIds = [];
            foreach ($postedSidebarOrderIds as $groupKey => $id) {
                $groupKey = strtolower(trim((string) $groupKey));
                if ($groupKey === '') {
                    continue;
                }
                $numeric = max(1, (int) $id);
                if ($groupKey === 'dashboard') {
                    $numeric = 1;
                } elseif ($numeric <= 1) {
                    $numeric = 2;
                }
                $normalizedSidebarOrderIds[$groupKey] = $numeric;
            }

            if (!isset($normalizedSidebarOrderIds['dashboard'])) {
                $normalizedSidebarOrderIds['dashboard'] = 1;
            }

            $sortableSidebarOrderIds = $normalizedSidebarOrderIds;
            uasort($sortableSidebarOrderIds, static fn (int $a, int $b): int => $a <=> $b);
            $computedSidebarOrder = implode(',', array_keys($sortableSidebarOrderIds));
            $data['ui'] = [
                'theme_default' => trim((string) ($post['theme_default'] ?? ($data['ui']['theme_default'] ?? 'corporate'))),
                'compact_sidebar' => ((string) ($post['compact_sidebar'] ?? '0')) === '1',
                'table_density' => trim((string) ($post['table_density'] ?? ($data['ui']['table_density'] ?? 'comfortable'))),
                'show_debug' => ((string) ($post['show_debug'] ?? '0')) === '1',
                'sidebar_order' => $computedSidebarOrder !== ''
                    ? $computedSidebarOrder
                    : trim((string) ($post['sidebar_order'] ?? ($data['ui']['sidebar_order'] ?? ''))),
                'sidebar_item_order' => trim((string) ($post['sidebar_item_order'] ?? ($data['ui']['sidebar_item_order'] ?? ''))),
                'sidebar_order_ids' => $normalizedSidebarOrderIds,
            ];
            $data['maintenance'] = [
                'enabled' => ((string) ($post['maintenance_enabled'] ?? '0')) === '1',
                'message' => trim((string) ($post['maintenance_message'] ?? ($data['maintenance']['message'] ?? 'Maintenance en cours'))),
                'allow_admin' => ((string) ($post['maintenance_allow_admin'] ?? '0')) === '1',
            ];
            $backupSource = is_array($data['backup'] ?? null) ? $data['backup'] : [];
            $data['backup'] = [
                'local_enabled' => array_key_exists('backup_local_enabled', $post)
                    ? ((string) ($post['backup_local_enabled'] ?? '0')) === '1'
                    : ((bool) ($backupSource['local_enabled'] ?? true)),
            ];
            $systemSource = is_array($data['system'] ?? null) ? $data['system'] : [];
            $data['system'] = [
                'cron_enabled' => array_key_exists('cron_enabled', $post)
                    ? ((string) ($post['cron_enabled'] ?? '0')) === '1'
                    : ((bool) ($systemSource['cron_enabled'] ?? true)),
            ];

            $ok = true;
            $persistableGroups = ['general', 'security', 'mail', 'ui', 'maintenance', 'backup', 'system'];
            foreach ($persistableGroups as $group) {
                $values = $data[$group] ?? null;
                if (!is_array($values)) {
                    continue;
                }
                foreach ($values as $key => $value) {
                    if (!$engine->set((string) $group . '.' . (string) $key, $value, true)) {
                        $ok = false;
                    }
                }
            }
            $ok = $ok && $engine->save();
            if ($ok) {
                $ok = $ok && $upsertCoreSetting($pdo, $coreSettingsTable, 'app', 'name', (string) $data['general']['app_name'], true);
                $ok = $ok && $upsertCoreSetting($pdo, $coreSettingsTable, 'security', 'admin_path', (string) $data['general']['admin_path'], false);
                $ok = $ok && $upsertCoreSetting($pdo, $coreSettingsTable, 'system', 'timezone', (string) $data['general']['timezone'], true);
            }
            if ($ok) {
                $appendCoreLog($pdo, $coreLogsTable, 'system', 'info', 'Parametres admin enregistres', ['section' => $section]);
            }
            return $redirect($adminBase . '/settings/' . $section, [
                'msg' => $ok ? 'Parametres enregistres en base.' : 'Echec ecriture base de donnees.',
                'mt' => $ok ? 'success' : 'danger',
            ]);
        },
        'middleware' => [$authRequired, $csrfCheck],
    ],

    [
        'method' => 'POST',
        'path' => '/settings/{section}',
        'where' => ['section' => 'general|appearance|sidebar|mail|performance|security|advanced|apps|module-repositories'],
        'handler' => static function (Request $request, string $section) use ($upsertCoreSetting, $redirect, $coreSettingsTable, $appendCoreLog, $coreLogsTable, $appsValidator, $appsRepository, $notificationsRepository): Response {
            $controller = new AuthController();
            $adminBase = $controller->adminBasePath();
            $pdo = (new ConnectionManager())->connection();
            $engine = new CoreSettingsEngine();
            $registry = new CoreModuleRepositoryRegistry();

            $data = $engine->all();
            $data['general'] = is_array($data['general'] ?? null) ? $data['general'] : [];
            $data['security'] = is_array($data['security'] ?? null) ? $data['security'] : [];
            $data['mail'] = is_array($data['mail'] ?? null) ? $data['mail'] : [];
            $data['ui'] = is_array($data['ui'] ?? null) ? $data['ui'] : [];
            $data['maintenance'] = is_array($data['maintenance'] ?? null) ? $data['maintenance'] : [];
            $post = $request->post();
            $rawSection = strtolower(trim($section));
            $section = match ($rawSection) {
                'mail' => 'mail',
                'security' => 'security',
                'appearance' => 'appearance',
                'sidebar' => 'sidebar',
                'performance' => 'performance',
                'advanced', 'apps', 'module-repositories', 'market' => 'advanced',
                default => 'general',
            };

            if ($rawSection === 'apps') {
                try {
                    (new \CoreDbUpgradeRunner())->run();
                } catch (\Throwable $exception) {
                    \Core\logs\Logger::error('Apps settings auto-upgrade failed', ['error' => $exception->getMessage()]);
                }

                $action = strtolower(trim((string) ($post['action'] ?? 'create')));
                $appId = (int) ($post['app_id'] ?? 0);
                $validation = $appsValidator->validate($post);

                if (in_array($action, ['create', 'update'], true) && !($validation['ok'] ?? false)) {
                return $redirect($adminBase . '/settings/advanced', [
                    'msg' => implode(' ', (array) ($validation['errors'] ?? ['Validation app invalide.'])),
                    'mt' => 'danger',
                ]);
            }

                $ok = false;
                if ($action === 'create') {
                    $ok = $appsRepository->create((array) ($validation['data'] ?? []));
                    if ($ok) {
                        $notificationsRepository->push([
                            'title' => 'Nouvelle app',
                            'message' => 'App ajoutee dans le launcher topbar.',
                            'type' => 'success',
                            'source' => 'settings.apps',
                            'action_url' => $adminBase . '/settings/advanced',
                        ]);
                    }
                } elseif ($action === 'update') {
                    $ok = $appsRepository->update($appId, (array) ($validation['data'] ?? []));
                } elseif ($action === 'delete') {
                    $ok = $appsRepository->delete($appId);
                } elseif ($action === 'toggle') {
                    $current = $appsRepository->find($appId);
                    if (is_array($current)) {
                        $current['is_enabled'] = !((bool) ($current['is_enabled'] ?? false));
                        $ok = $appsRepository->update($appId, $current);
                    }
                }

                $errorMessage = trim($appsRepository->lastError());
                return $redirect($adminBase . '/settings/advanced', [
                    'msg' => $ok ? 'Apps enregistrees.' : ($errorMessage !== '' ? ('Echec operation apps. ' . $errorMessage) : 'Echec operation apps.'),
                    'mt' => $ok ? 'success' : 'danger',
                ]);
            }

            if ($rawSection === 'module-repositories') {
                $action = strtolower(trim((string) ($post['action'] ?? 'create')));
                $id = (int) ($post['repository_id'] ?? 0);

                $result = ['ok' => false, 'message' => 'Action non supportée.'];
                if ($action === 'create') {
                    $result = $registry->addRepository($post);
                } elseif ($action === 'update' && $id > 0) {
                    $result = $registry->updateRepository($id, $post);
                } elseif ($action === 'toggle' && $id > 0) {
                    $row = $registry->listRepositories();
                    $current = null;
                    foreach ($row as $repoRow) {
                        if ((int) ($repoRow['id'] ?? 0) === $id) {
                            $current = $repoRow;
                            break;
                        }
                    }
                    $enabled = (bool) (($current['is_enabled'] ?? false));
                    $result = $enabled ? $registry->disableRepository($id) : $registry->enableRepository($id);
                } elseif ($action === 'check' && $id > 0) {
                    $result = $registry->checkRepository($id);
                } elseif ($action === 'block' && $id > 0) {
                    $candidate = null;
                    foreach ($registry->listRepositories() as $repoRow) {
                        if ((int) ($repoRow['id'] ?? 0) === $id) {
                            $candidate = $repoRow;
                            break;
                        }
                    }
                    if (is_array($candidate)) {
                        $candidate['trust_level'] = 'blocked';
                        $result = $registry->updateRepository($id, $candidate);
                    }
                } elseif ($action === 'delete' && $id > 0) {
                    $result = $registry->removeRepository($id);
                } elseif ($action === 'save_policy') {
                    $result = $registry->savePolicy($post);
                }

                return $redirect($adminBase . '/settings/advanced', [
                    'msg' => (string) ($result['message'] ?? 'Opération terminée.'),
                    'mt' => (bool) ($result['ok'] ?? false) ? 'success' : 'danger',
                ]);
            }
            $postedTimezone = trim((string) ($post['timezone'] ?? ($data['general']['timezone'] ?? 'UTC')));
            if (!in_array($postedTimezone, \DateTimeZone::listIdentifiers(), true)) {
                $postedTimezone = (string) ($data['general']['timezone'] ?? 'UTC');
            }

            $data['general'] = [
                'app_name' => trim((string) ($post['app_name'] ?? ($data['general']['app_name'] ?? 'CATMIN'))),
                'app_env' => trim((string) ($post['app_env'] ?? ($data['general']['app_env'] ?? 'production'))),
                'timezone' => $postedTimezone,
                'admin_path' => trim((string) ($post['admin_path'] ?? ($data['general']['admin_path'] ?? 'admin'))),
            ];
            $data['security'] = [
                'session_minutes' => max(15, (int) ($post['session_minutes'] ?? ($data['security']['session_minutes'] ?? 120))),
                'max_attempts' => max(3, (int) ($post['max_attempts'] ?? ($data['security']['max_attempts'] ?? 5))),
                'password_min' => max(8, (int) ($post['password_min'] ?? ($data['security']['password_min'] ?? 12))),
                'enforce_2fa' => ((string) ($post['enforce_2fa'] ?? '0')) === '1',
            ];
            $mailSource = is_array($data['mail'] ?? null) ? $data['mail'] : [];
            $data['mail'] = [
                'enabled' => ((string) ($post['email_enabled'] ?? '0')) === '1',
                'driver' => trim((string) ($post['email_driver'] ?? ($mailSource['driver'] ?? 'smtp'))),
                'from_name' => trim((string) ($post['email_from_name'] ?? ($mailSource['from_name'] ?? 'CATMIN'))),
                'from_email' => trim((string) ($post['email_from_email'] ?? ($mailSource['from_email'] ?? 'noreply@example.com'))),
                'host' => trim((string) ($post['email_host'] ?? ($mailSource['host'] ?? ''))),
                'port' => max(1, (int) ($post['email_port'] ?? ($mailSource['port'] ?? 587))),
                'encryption' => trim((string) ($post['email_encryption'] ?? ($mailSource['encryption'] ?? 'tls'))),
                'username' => trim((string) ($post['email_username'] ?? ($mailSource['username'] ?? ''))),
            ];
            $postedSidebarOrderIds = is_array($post['sidebar_order_ids'] ?? null) ? (array) $post['sidebar_order_ids'] : [];
            $normalizedSidebarOrderIds = [];
            foreach ($postedSidebarOrderIds as $groupKey => $id) {
                $groupKey = strtolower(trim((string) $groupKey));
                if ($groupKey === '') {
                    continue;
                }
                $numeric = max(1, (int) $id);
                if ($groupKey === 'dashboard') {
                    $numeric = 1;
                } elseif ($numeric <= 1) {
                    $numeric = 2;
                }
                $normalizedSidebarOrderIds[$groupKey] = $numeric;
            }

            if (!isset($normalizedSidebarOrderIds['dashboard'])) {
                $normalizedSidebarOrderIds['dashboard'] = 1;
            }

            $sortableSidebarOrderIds = $normalizedSidebarOrderIds;
            uasort($sortableSidebarOrderIds, static fn (int $a, int $b): int => $a <=> $b);
            $computedSidebarOrder = implode(',', array_keys($sortableSidebarOrderIds));
            $data['ui'] = [
                'theme_default' => trim((string) ($post['theme_default'] ?? ($data['ui']['theme_default'] ?? 'corporate'))),
                'compact_sidebar' => ((string) ($post['compact_sidebar'] ?? '0')) === '1',
                'table_density' => trim((string) ($post['table_density'] ?? ($data['ui']['table_density'] ?? 'comfortable'))),
                'show_debug' => ((string) ($post['show_debug'] ?? '0')) === '1',
                'sidebar_order' => $computedSidebarOrder !== ''
                    ? $computedSidebarOrder
                    : trim((string) ($post['sidebar_order'] ?? ($data['ui']['sidebar_order'] ?? ''))),
                'sidebar_item_order' => trim((string) ($post['sidebar_item_order'] ?? ($data['ui']['sidebar_item_order'] ?? ''))),
                'sidebar_order_ids' => $normalizedSidebarOrderIds,
            ];
            $data['maintenance'] = [
                'enabled' => ((string) ($post['maintenance_enabled'] ?? '0')) === '1',
                'message' => trim((string) ($post['maintenance_message'] ?? ($data['maintenance']['message'] ?? 'Maintenance en cours'))),
                'allow_admin' => ((string) ($post['maintenance_allow_admin'] ?? '0')) === '1',
            ];
            $backupSource = is_array($data['backup'] ?? null) ? $data['backup'] : [];
            $data['backup'] = [
                'local_enabled' => array_key_exists('backup_local_enabled', $post)
                    ? ((string) ($post['backup_local_enabled'] ?? '0')) === '1'
                    : ((bool) ($backupSource['local_enabled'] ?? true)),
            ];
            $systemSource = is_array($data['system'] ?? null) ? $data['system'] : [];
            $data['system'] = [
                'cron_enabled' => array_key_exists('cron_enabled', $post)
                    ? ((string) ($post['cron_enabled'] ?? '0')) === '1'
                    : ((bool) ($systemSource['cron_enabled'] ?? true)),
            ];

            $ok = true;
            $persistableGroups = ['general', 'security', 'mail', 'ui', 'maintenance', 'backup', 'system'];
            foreach ($persistableGroups as $group) {
                $values = $data[$group] ?? null;
                if (!is_array($values)) {
                    continue;
                }
                foreach ($values as $key => $value) {
                    if (!$engine->set((string) $group . '.' . (string) $key, $value, true)) {
                        $ok = false;
                    }
                }
            }
            $ok = $ok && $engine->save();
            if ($ok) {
                $ok = $ok && $upsertCoreSetting($pdo, $coreSettingsTable, 'app', 'name', (string) $data['general']['app_name'], true);
                $ok = $ok && $upsertCoreSetting($pdo, $coreSettingsTable, 'security', 'admin_path', (string) $data['general']['admin_path'], false);
                $ok = $ok && $upsertCoreSetting($pdo, $coreSettingsTable, 'system', 'timezone', (string) $data['general']['timezone'], true);
            }
            if ($ok) {
                $appendCoreLog($pdo, $coreLogsTable, 'system', 'info', 'Parametres admin enregistres', ['section' => $section]);
            }
            return $redirect($adminBase . '/settings/' . $section, [
                'msg' => $ok ? 'Parametres enregistres en base.' : 'Echec ecriture base de donnees.',
                'mt' => $ok ? 'success' : 'danger',
            ]);
        },
        'middleware' => [$authRequired, $csrfCheck],
    ],

    [
        'method' => 'GET',
        'path' => '/logs',
        'handler' => static function (Request $request) use ($eventsTable, $usersTable, $loadSystemLogs, $coreLogsTable): Response {
            $controller = new AuthController();
            $adminBase = $controller->adminBasePath();
            $pdo = (new ConnectionManager())->connection();

            $source = strtolower(trim((string) $request->input('source', 'all')));
            $search = strtolower(trim((string) $request->input('q', '')));
            $level = strtoupper(trim((string) $request->input('level', 'ALL')));

            $systemLogs = $loadSystemLogs($pdo, $coreLogsTable, 300);
            if ($search !== '') {
                $systemLogs = array_values(array_filter($systemLogs, static fn (array $row): bool => str_contains(strtolower((string) ($row['message'] ?? '')), $search)));
            }
            if ($level !== 'ALL') {
                $systemLogs = array_values(array_filter($systemLogs, static fn (array $row): bool => strtoupper((string) ($row['level'] ?? 'INFO')) === $level));
            }

            $securityLogs = [];
            try {
                $stmt = $pdo->query('SELECT event_type, severity, ip_address, created_at, message FROM ' . $eventsTable . ' ORDER BY created_at DESC LIMIT 200');
                $securityLogs = $stmt !== false ? ($stmt->fetchAll(\PDO::FETCH_ASSOC) ?: []) : [];
            } catch (\Throwable) {
                $securityLogs = [];
            }
            if ($search !== '') {
                $securityLogs = array_values(array_filter($securityLogs, static function (array $row) use ($search): bool {
                    $txt = strtolower((string) (($row['event_type'] ?? '') . ' ' . ($row['message'] ?? '') . ' ' . ($row['ip_address'] ?? '')));
                    return str_contains($txt, $search);
                }));
            }

            $adminActivity = [];
            try {
                $stmt = $pdo->query('SELECT username, email, last_login_at, updated_at, is_active FROM ' . $usersTable . ' ORDER BY updated_at DESC LIMIT 100');
                $adminActivity = $stmt !== false ? ($stmt->fetchAll(\PDO::FETCH_ASSOC) ?: []) : [];
            } catch (\Throwable) {
                $adminActivity = [];
            }
            if ($search !== '') {
                $adminActivity = array_values(array_filter($adminActivity, static function (array $row) use ($search): bool {
                    $txt = strtolower((string) (($row['username'] ?? '') . ' ' . ($row['email'] ?? '')));
                    return str_contains($txt, $search);
                }));
            }

            return View::make('logs.index', [
                'adminBase' => $adminBase,
                'filters' => [
                    'source' => $source,
                    'q' => $search,
                    'level' => $level,
                ],
                'systemLogs' => $systemLogs,
                'securityLogs' => $securityLogs,
                'adminActivity' => $adminActivity,
            ], 'admin');
        },
        'middleware' => [$authRequired],
    ],

    [
        'method' => 'GET',
        'path' => '/system/health',
        'handler' => static function (): Response {
            $controller = new AuthController();
            $adminBase = $controller->adminBasePath();
            $snapshot = (new HealthCheckService())->run();

            return View::make('system.health', [
                'adminBase' => $adminBase,
                'snapshot' => $snapshot,
            ], 'admin');
        },
        'middleware' => [$authRequired],
    ],

    [
        'method' => 'GET',
        'path' => '/system/monitoring',
        'handler' => static function (): Response {
            $controller = new AuthController();
            $adminBase = $controller->adminBasePath();
            $snapshot = (new MonitoringService())->snapshot();

            return View::make('system.monitoring', [
                'adminBase' => $adminBase,
                'snapshot' => $snapshot,
            ], 'admin');
        },
        'middleware' => [$authRequired],
    ],

    [
        'method' => 'GET',
        'path' => '/system/trust-center',
        'handler' => static function (): Response {
            $controller = new AuthController();
            $adminBase = $controller->adminBasePath();
            $snapshot = (new CoreTrustCenter())->snapshot();

            return View::make('system.trust-center', [
                'adminBase' => $adminBase,
                'snapshot' => $snapshot,
            ], 'admin');
        },
        'middleware' => [$authRequired],
    ],

    [
        'method' => 'POST',
        'path' => '/system/trust-center/local-keys/add',
        'handler' => static function (Request $request) use ($redirect): Response {
            $controller = new AuthController();
            $adminBase = $controller->adminBasePath();
            $result = (new CoreTrustCenter())->addLocalKey([
                'key_id' => (string) $request->input('key_id', ''),
                'publisher' => (string) $request->input('publisher', ''),
                'public_key' => (string) $request->input('public_key', ''),
            ]);

            return $redirect($adminBase . '/system/trust-center', [
                'msg' => (string) ($result['message'] ?? ''),
                'mt' => (bool) ($result['ok'] ?? false) ? 'success' : 'danger',
            ]);
        },
        'middleware' => [$authRequired, $csrfCheck],
    ],

    [
        'method' => 'POST',
        'path' => '/system/trust-center/local-keys/delete',
        'handler' => static function (Request $request) use ($redirect): Response {
            $controller = new AuthController();
            $adminBase = $controller->adminBasePath();
            $result = (new CoreTrustCenter())->removeLocalKey((string) $request->input('key_id', ''));

            return $redirect($adminBase . '/system/trust-center', [
                'msg' => (string) ($result['message'] ?? ''),
                'mt' => (bool) ($result['ok'] ?? false) ? 'success' : 'danger',
            ]);
        },
        'middleware' => [$authRequired, $csrfCheck],
    ],

    [
        'method' => 'POST',
        'path' => '/system/trust-center/revoke',
        'handler' => static function (Request $request) use ($redirect): Response {
            $controller = new AuthController();
            $adminBase = $controller->adminBasePath();
            $result = (new CoreTrustCenter())->revokeKey(
                (string) $request->input('key_id', ''),
                (string) $request->input('reason', '')
            );

            return $redirect($adminBase . '/system/trust-center', [
                'msg' => (string) ($result['message'] ?? ''),
                'mt' => (bool) ($result['ok'] ?? false) ? 'success' : 'danger',
            ]);
        },
        'middleware' => [$authRequired, $csrfCheck],
    ],

    [
        'method' => 'POST',
        'path' => '/system/trust-center/import',
        'handler' => static function (Request $request) use ($redirect): Response {
            $controller = new AuthController();
            $adminBase = $controller->adminBasePath();
            $result = (new CoreTrustCenter())->importOfficialKeyringFromJson((string) $request->input('import_json', ''));

            return $redirect($adminBase . '/system/trust-center', [
                'msg' => (string) ($result['message'] ?? ''),
                'mt' => (bool) ($result['ok'] ?? false) ? 'success' : 'danger',
            ]);
        },
        'middleware' => [$authRequired, $csrfCheck],
    ],

    [
        'method' => 'POST',
        'path' => '/system/trust-center/sync',
        'handler' => static function () use ($redirect): Response {
            $controller = new AuthController();
            $adminBase = $controller->adminBasePath();
            $result = (new CoreTrustCenter())->syncRemote();

            return $redirect($adminBase . '/system/trust-center', [
                'msg' => (string) ($result['message'] ?? ''),
                'mt' => (bool) ($result['ok'] ?? false) ? 'success' : 'warning',
            ]);
        },
        'middleware' => [$authRequired, $csrfCheck],
    ],

    [
        'method' => 'GET',
        'path' => '/system/update',
        'handler' => static function (): Response {
            $controller = new AuthController();
            $adminBase = $controller->adminBasePath();
            return Response::html('', 302, ['Location' => $adminBase . '/system/updates']);
        },
        'middleware' => [$authRequired],
    ],

    [
        'method' => 'POST',
        'path' => '/system/update/check',
        'handler' => static function () use ($redirect): Response {
            $controller = new AuthController();
            $adminBase = $controller->adminBasePath();

            $check = (new CoreUpdater())->check();
            return $redirect($adminBase . '/system/updates', [
                'msg' => (bool) ($check['ok'] ?? false) ? 'Vérification update terminée.' : ((string) ($check['error'] ?? 'Vérification update en erreur.')),
                'mt' => (bool) ($check['ok'] ?? false) ? 'success' : 'danger',
            ]);
        },
        'middleware' => [$authRequired, $csrfCheck],
    ],

    [
        'method' => 'POST',
        'path' => '/system/update/dry-run',
        'handler' => static function () use ($redirect): Response {
            $controller = new AuthController();
            $adminBase = $controller->adminBasePath();

            $result = (new CoreUpdater())->dryRun();
            return $redirect($adminBase . '/system/updates', [
                'msg' => (bool) ($result['ok'] ?? false) ? 'Dry-run update terminé.' : ((string) ($result['error'] ?? 'Dry-run en erreur.')),
                'mt' => (bool) ($result['ok'] ?? false) ? 'success' : 'danger',
            ]);
        },
        'middleware' => [$authRequired, $csrfCheck],
    ],

    [
        'method' => 'POST',
        'path' => '/system/update/run',
        'handler' => static function () use ($redirect): Response {
            $controller = new AuthController();
            $adminBase = $controller->adminBasePath();

            $result = (new CoreUpdater())->updateNow();
            return $redirect($adminBase . '/system/updates', [
                'msg' => (bool) ($result['ok'] ?? false) ? 'Update core terminée.' : ((string) ($result['error'] ?? 'Update core en erreur.')),
                'mt' => (bool) ($result['ok'] ?? false) ? 'success' : 'danger',
            ]);
        },
        'middleware' => [$authRequired, $csrfCheck],
    ],

    [
        'method' => 'GET',
        'path' => '/system/updates',
        'handler' => static function () use ($consumeFlash): Response {
            $controller = new AuthController();
            $adminBase = $controller->adminBasePath();
            $flash = $consumeFlash();
            $snapshot = (new CoreUpdateCenter())->buildSnapshot();

            return View::make('system.updates', [
                'adminBase' => $adminBase,
                'snapshot' => $snapshot,
                'message' => (string) ($flash['message'] ?? ''),
                'messageType' => (string) ($flash['type'] ?? 'success'),
            ], 'admin');
        },
        'middleware' => [$authRequired],
    ],

    [
        'method' => 'POST',
        'path' => '/system/updates/check',
        'handler' => static function () use ($redirect): Response {
            $controller = new AuthController();
            $adminBase = $controller->adminBasePath();
            $check = (new CoreUpdater())->check();
            if ((bool) ($check['ok'] ?? false)) {
                $snapshot = (new CoreUpdateCenter())->buildSnapshot();
                (new CoreUpdateIntelligentNotifier())->notify($snapshot, $adminBase);
                $telemetry = new CoreTelemetryMinimal();
                if ($telemetry->isEnabled()) {
                    $telemetry->store($telemetry->buildSnapshot(['source' => 'updates.check']), 'minimal');
                }
            }
            return $redirect($adminBase . '/system/updates', [
                'msg' => (bool) ($check['ok'] ?? false) ? 'Vérification update terminée.' : ((string) ($check['error'] ?? 'Vérification update en erreur.')),
                'mt' => (bool) ($check['ok'] ?? false) ? 'success' : 'danger',
            ]);
        },
        'middleware' => [$authRequired, $csrfCheck],
    ],

    [
        'method' => 'POST',
        'path' => '/system/updates/dry-run',
        'handler' => static function () use ($redirect): Response {
            $controller = new AuthController();
            $adminBase = $controller->adminBasePath();
            $result = (new CoreUpdater())->dryRun();
            return $redirect($adminBase . '/system/updates', [
                'msg' => (bool) ($result['ok'] ?? false) ? 'Dry-run update terminé.' : ((string) ($result['error'] ?? 'Dry-run en erreur.')),
                'mt' => (bool) ($result['ok'] ?? false) ? 'success' : 'danger',
            ]);
        },
        'middleware' => [$authRequired, $csrfCheck],
    ],

    [
        'method' => 'POST',
        'path' => '/system/updates/run',
        'handler' => static function () use ($redirect): Response {
            $controller = new AuthController();
            $adminBase = $controller->adminBasePath();
            $result = (new CoreUpdater())->updateNow();
            return $redirect($adminBase . '/system/updates', [
                'msg' => (bool) ($result['ok'] ?? false) ? 'Update core terminée.' : ((string) ($result['error'] ?? 'Update core en erreur.')),
                'mt' => (bool) ($result['ok'] ?? false) ? 'success' : 'danger',
            ]);
        },
        'middleware' => [$authRequired, $csrfCheck],
    ],

    [
        'method' => 'GET',
        'path' => '/system/queue',
        'handler' => static function () use ($consumeFlash): Response {
            $controller = new AuthController();
            $adminBase = $controller->adminBasePath();
            $flash = $consumeFlash();
            $queue = new CoreQueueEngine();
            $rows = $queue->listRecent('default', 200);
            $stats = $queue->stats('default');

            return View::make('system.queue', [
                'adminBase' => $adminBase,
                'rows' => $rows,
                'stats' => $stats,
                'message' => (string) ($flash['message'] ?? ''),
                'messageType' => (string) ($flash['type'] ?? 'success'),
            ], 'admin');
        },
        'middleware' => [$authRequired],
    ],

    [
        'method' => 'POST',
        'path' => '/system/queue/enqueue-test',
        'handler' => static function () use ($redirect): Response {
            $controller = new AuthController();
            $adminBase = $controller->adminBasePath();
            $result = (new CoreQueueEngine())->enqueue('core.test.ping', [
                'at' => gmdate('c'),
                'from' => 'admin-ui',
            ], 'default', 0, 2);

            return $redirect($adminBase . '/system/queue', [
                'msg' => (bool) ($result['ok'] ?? false) ? 'Job de test ajouté à la queue.' : ((string) ($result['message'] ?? 'Échec queue.')),
                'mt' => (bool) ($result['ok'] ?? false) ? 'success' : 'danger',
            ]);
        },
        'middleware' => [$authRequired, $csrfCheck],
    ],

    [
        'method' => 'GET',
        'path' => '/cron',
        'handler' => static function (Request $request) use ($coreCronTasksTable, $coreLogsTable, $ensureCronTasksTable, $seedDefaultCronTasks, $ensureCronDirectory, $consumeFlash): Response {
            $controller = new AuthController();
            $adminBase = $controller->adminBasePath();
            $pdo = (new ConnectionManager())->connection();
            $flash = $consumeFlash();
            $ensureCronDirectory();
            $ensureCronTasksTable($pdo, $coreCronTasksTable);
            $seedDefaultCronTasks($pdo, $coreCronTasksTable);

            $stmt = $pdo->query('SELECT id, name, script_path, schedule_expr, is_active, last_run_at, created_at, updated_at FROM ' . $coreCronTasksTable . ' ORDER BY is_active DESC, name ASC');
            $tasks = $stmt !== false ? ($stmt->fetchAll(\PDO::FETCH_ASSOC) ?: []) : [];

            $historyStmt = $pdo->prepare('SELECT created_at, level, message FROM ' . $coreLogsTable . ' WHERE channel = :channel ORDER BY created_at DESC LIMIT :limit');
            $historyStmt->bindValue(':channel', 'cron', \PDO::PARAM_STR);
            $historyStmt->bindValue(':limit', 50, \PDO::PARAM_INT);
            $historyStmt->execute();
            $history = $historyStmt->fetchAll(\PDO::FETCH_ASSOC);
            if (!is_array($history)) {
                $history = [];
            }

            return View::make('cron.index', [
                'adminBase' => $adminBase,
                'tasks' => $tasks,
                'history' => $history,
                'message' => (string) ($flash['message'] ?? ''),
                'messageType' => (string) ($flash['type'] ?? 'success'),
            ], 'admin');
        },
        'middleware' => [$authRequired],
    ],

    [
        'method' => 'POST',
        'path' => '/cron/create',
        'handler' => static function (Request $request) use ($coreCronTasksTable, $ensureCronTasksTable, $redirect, $appendCoreLog, $coreLogsTable, $ensureCronDirectory): Response {
            $controller = new AuthController();
            $adminBase = $controller->adminBasePath();
            $pdo = (new ConnectionManager())->connection();
            $ensureCronDirectory();
            $ensureCronTasksTable($pdo, $coreCronTasksTable);

            $name = trim((string) $request->input('name', ''));
            $scriptPath = trim((string) $request->input('script_path', ''));
            $scheduleExpr = trim((string) $request->input('schedule_expr', '*/5 * * * *'));
            $isActive = (string) $request->input('is_active', '1') === '1' ? 1 : 0;

            if ($name === '' || $scriptPath === '' || $scheduleExpr === '') {
                return $redirect($adminBase . '/cron', ['msg' => 'Nom, script et schedule sont requis.', 'mt' => 'danger']);
            }
            $normalizedScriptPath = ltrim(str_replace('\\', '/', $scriptPath), '/');
            if (!preg_match('#^cron/[a-zA-Z0-9_\\-/]+\\.php$#', $normalizedScriptPath)) {
                return $redirect($adminBase . '/cron', ['msg' => 'Script utilisateur invalide. Utilise uniquement cron/*.php', 'mt' => 'danger']);
            }

            $insert = $pdo->prepare(
                'INSERT INTO ' . $coreCronTasksTable . ' (name, script_path, schedule_expr, is_active, created_at, updated_at) VALUES (:name, :script_path, :schedule_expr, :is_active, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
            );
            $ok = $insert->execute([
                'name' => $name,
                'script_path' => $normalizedScriptPath,
                'schedule_expr' => $scheduleExpr,
                'is_active' => $isActive,
            ]);
            if ($ok) {
                $appendCoreLog($pdo, $coreLogsTable, 'cron', 'info', 'Tache cron creee', ['name' => $name, 'script' => $normalizedScriptPath, 'schedule' => $scheduleExpr]);
            }

            return $redirect($adminBase . '/cron', [
                'msg' => $ok ? 'Tache cron creee.' : 'Echec creation tache cron.',
                'mt' => $ok ? 'success' : 'danger',
            ]);
        },
        'middleware' => [$authRequired, $csrfCheck],
    ],

    [
        'method' => 'POST',
        'path' => '/cron/toggle',
        'handler' => static function (Request $request) use ($coreCronTasksTable, $ensureCronTasksTable, $redirect, $appendCoreLog, $coreLogsTable): Response {
            $controller = new AuthController();
            $adminBase = $controller->adminBasePath();
            $pdo = (new ConnectionManager())->connection();
            $ensureCronTasksTable($pdo, $coreCronTasksTable);

            $id = max(1, (int) $request->input('id', 0));
            $active = (string) $request->input('active', '0') === '1' ? 1 : 0;
            $update = $pdo->prepare('UPDATE ' . $coreCronTasksTable . ' SET is_active = :is_active, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
            $ok = $update->execute(['is_active' => $active, 'id' => $id]);
            if ($ok) {
                $appendCoreLog($pdo, $coreLogsTable, 'cron', 'info', $active ? 'Tache cron activee' : 'Tache cron desactivee', ['id' => $id]);
            }

            return $redirect($adminBase . '/cron', [
                'msg' => $ok ? 'Etat de la tache mis a jour.' : 'Echec mise a jour tache.',
                'mt' => $ok ? 'success' : 'danger',
            ]);
        },
        'middleware' => [$authRequired, $csrfCheck],
    ],

    [
        'method' => 'POST',
        'path' => '/cron/delete',
        'handler' => static function (Request $request) use ($coreCronTasksTable, $ensureCronTasksTable, $redirect, $appendCoreLog, $coreLogsTable): Response {
            $controller = new AuthController();
            $adminBase = $controller->adminBasePath();
            $pdo = (new ConnectionManager())->connection();
            $ensureCronTasksTable($pdo, $coreCronTasksTable);

            $id = max(1, (int) $request->input('id', 0));
            $delete = $pdo->prepare('DELETE FROM ' . $coreCronTasksTable . ' WHERE id = :id');
            $ok = $delete->execute(['id' => $id]);
            if ($ok) {
                $appendCoreLog($pdo, $coreLogsTable, 'cron', 'warning', 'Tache cron supprimee', ['id' => $id]);
            }

            return $redirect($adminBase . '/cron', [
                'msg' => $ok ? 'Tache supprimee.' : 'Echec suppression tache.',
                'mt' => $ok ? 'warning' : 'danger',
            ]);
        },
        'middleware' => [$authRequired, $csrfCheck],
    ],

    [
        'method' => 'POST',
        'path' => '/cron/run',
        'handler' => static function (Request $request) use ($coreCronTasksTable, $ensureCronTasksTable, $redirect, $appendCoreLog, $coreLogsTable, $ensureCronDirectory): Response {
            $controller = new AuthController();
            $adminBase = $controller->adminBasePath();
            $pdo = (new ConnectionManager())->connection();
            $ensureCronDirectory();
            $ensureCronTasksTable($pdo, $coreCronTasksTable);

            $id = max(1, (int) $request->input('id', 0));
            $find = $pdo->prepare('SELECT id, name, script_path, is_active FROM ' . $coreCronTasksTable . ' WHERE id = :id LIMIT 1');
            $find->execute(['id' => $id]);
            $task = $find->fetch(\PDO::FETCH_ASSOC);
            if (!is_array($task)) {
                return $redirect($adminBase . '/cron', ['msg' => 'Tache introuvable.', 'mt' => 'danger']);
            }

            $scriptPath = ltrim(str_replace('\\', '/', trim((string) ($task['script_path'] ?? ''))), '/');
            $isUserScript = preg_match('#^cron/[a-zA-Z0-9_\\-/]+\\.php$#', $scriptPath) === 1;
            $isCoreScript = preg_match('#^core/cron/[a-zA-Z0-9_\\-/]+\\.php$#', $scriptPath) === 1;
            if (!$isUserScript && !$isCoreScript) {
                return $redirect($adminBase . '/cron', ['msg' => 'Script hors zone autorisee: ' . $scriptPath, 'mt' => 'danger']);
            }

            $candidate = CATMIN_ROOT . '/' . $scriptPath;
            $real = realpath($candidate);
            if (!is_string($real) || $real === '' || !str_starts_with($real, CATMIN_ROOT . '/') || !is_file($real) || strtolower(pathinfo($real, PATHINFO_EXTENSION)) !== 'php') {
                return $redirect($adminBase . '/cron', ['msg' => 'Script PHP invalide: ' . $scriptPath, 'mt' => 'danger']);
            }

            $command = 'php ' . escapeshellarg($real) . ' 2>&1';
            $output = [];
            $exitCode = 1;
            @exec($command, $output, $exitCode);
            $ok = $exitCode === 0;

            $update = $pdo->prepare('UPDATE ' . $coreCronTasksTable . ' SET last_run_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
            $update->execute(['id' => $id]);
            $appendCoreLog($pdo, $coreLogsTable, 'cron', $ok ? 'info' : 'error', ($ok ? 'Execution cron OK: ' : 'Execution cron KO: ') . (string) ($task['name'] ?? 'task'), [
                'id' => $id,
                'script' => $scriptPath,
                'exit_code' => $exitCode,
                'output' => implode("\n", array_slice($output, -5)),
            ]);

            return $redirect($adminBase . '/cron', [
                'msg' => $ok ? 'Execution OK: ' . (string) ($task['name'] ?? 'task') : 'Execution KO (code ' . $exitCode . ')',
                'mt' => $ok ? 'success' : 'danger',
            ]);
        },
        'middleware' => [$authRequired, $csrfCheck],
    ],

    [
        'method' => 'GET',
        'path' => '/maintenance',
        'handler' => static function (Request $request) use ($loadSystemState, $listBackups, $coreSettingsTable, $coreBackupsTable, $consumeFlash): Response {
            $controller = new AuthController();
            $adminBase = $controller->adminBasePath();
            $pdo = (new ConnectionManager())->connection();
            $flash = $consumeFlash();

            $state = $loadSystemState($pdo, $coreSettingsTable, $coreBackupsTable);
            $backups = $listBackups($pdo, $coreBackupsTable);

            return View::make('maintenance.index', [
                'adminBase' => $adminBase,
                'state' => $state,
                'backups' => $backups,
                'message' => (string) ($flash['message'] ?? ''),
                'messageType' => (string) ($flash['type'] ?? 'success'),
            ], 'admin');
        },
        'middleware' => [$authRequired],
    ],

    [
        'method' => 'POST',
        'path' => '/maintenance/toggle',
        'handler' => static function (Request $request) use ($loadSystemState, $saveSystemState, $redirect, $coreSettingsTable, $coreBackupsTable, $appendCoreLog, $coreLogsTable): Response {
            $controller = new AuthController();
            $adminBase = $controller->adminBasePath();
            $pdo = (new ConnectionManager())->connection();
            $state = $loadSystemState($pdo, $coreSettingsTable, $coreBackupsTable);
            $enabled = ((string) $request->input('maintenance', '0')) === '1';
            $state['maintenance'] = $enabled;
            $state['maintenance_level'] = max(1, min(3, (int) $request->input('maintenance_level', (int) ($state['maintenance_level'] ?? 1))));
            $state['maintenance_reason'] = trim((string) $request->input('maintenance_reason', (string) ($state['maintenance_reason'] ?? '')));
            $state['maintenance_message'] = trim((string) $request->input('maintenance_message', (string) ($state['maintenance_message'] ?? 'Maintenance en cours')));
            $state['maintenance_allow_admin'] = ((string) $request->input('maintenance_allow_admin', '0')) === '1';
            $state['maintenance_allowed_ips'] = trim((string) $request->input('maintenance_allowed_ips', (string) ($state['maintenance_allowed_ips'] ?? '')));
            $state['maintenance_allowed_admin_ids'] = trim((string) $request->input('maintenance_allowed_admin_ids', (string) ($state['maintenance_allowed_admin_ids'] ?? '')));
            $state['maintenance_started_at'] = $enabled ? date('Y-m-d H:i:s') : '';
            $state['maintenance_enabled_by'] = (string) (($controller->currentUser()['username'] ?? '') ?: ($controller->currentUser()['email'] ?? ''));
            $ok = $saveSystemState($pdo, $coreSettingsTable, $state);
            if ($ok) {
                $appendCoreLog(
                    $pdo,
                    $coreLogsTable,
                    'system',
                    'warning',
                    $state['maintenance'] ? 'Maintenance activee' : 'Maintenance desactivee',
                    [
                        'level' => (int) ($state['maintenance_level'] ?? 1),
                        'reason' => (string) ($state['maintenance_reason'] ?? ''),
                        'enabled_by' => (string) ($state['maintenance_enabled_by'] ?? ''),
                    ]
                );
            }
            return $redirect($adminBase . '/maintenance', [
                'msg' => $ok ? 'Etat maintenance mis a jour en base.' : 'Echec ecriture etat maintenance en base.',
                'mt' => $ok ? 'success' : 'danger',
            ]);
        },
        'middleware' => [$authRequired, $csrfCheck],
    ],

    [
        'method' => 'POST',
        'path' => '/maintenance/backup/create',
        'handler' => static function () use ($saveSystemState, $redirect, $coreSettingsTable, $coreBackupsTable, $appendCoreLog, $coreLogsTable, $buildSqlDump): Response {
            $controller = new AuthController();
            $adminBase = $controller->adminBasePath();
            $pdo = (new ConnectionManager())->connection();
            $dir = CATMIN_STORAGE . '/backups';
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            $driver = (string) config('database.default', 'sqlite');
            $createdAt = date('Y-m-d H:i:s');
            $filename = date('YmdHis');
            $ok = false;
            $target = '';
            $size = 0;
            $checksum = '';

            $zipName = $filename . '.zip';
            $target = $dir . '/' . $zipName;
            $meta = [
                'generated_at' => date('c'),
                'driver' => $driver,
                'app_env' => (string) config('app.env', 'production'),
                'version' => Version::current(),
            ];
            $sqlDump = $buildSqlDump($pdo);

            if (class_exists('ZipArchive')) {
                $zip = new \ZipArchive();
                $opened = $zip->open($target, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
                if ($opened === true) {
                    $zip->addFromString('dump.sql', $sqlDump);
                    $zip->addFromString('meta.json', (string) json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                    if ($driver === 'sqlite') {
                        $sqlitePath = (string) config('database.connections.sqlite.database', CATMIN_ROOT . '/db/database.sqlite');
                        if (is_file($sqlitePath)) {
                            $zip->addFile($sqlitePath, 'database.sqlite');
                        }
                    }
                    $ok = $zip->close();
                    $filename = $zipName;
                }
            }

            if (!$ok) {
                $sqlName = $filename . '.sql';
                $target = $dir . '/' . $sqlName;
                $ok = @file_put_contents($target, $sqlDump) !== false;
                $filename = $sqlName;
            }

            $size = $ok ? (int) (@filesize($target) ?: 0) : 0;
            $checksum = $ok ? (string) (@hash_file('sha256', $target) ?: '') : '';

            $insert = $pdo->prepare(
                'INSERT INTO ' . $coreBackupsTable . ' (backup_type, status, file_path, checksum, size_bytes, created_at) VALUES (:backup_type, :status, :file_path, :checksum, :size_bytes, :created_at)'
            );
            $insert->execute([
                'backup_type' => 'manual',
                'status' => $ok ? 'success' : 'failed',
                'file_path' => $target,
                'checksum' => $checksum !== '' ? $checksum : null,
                'size_bytes' => $size,
                'created_at' => $createdAt,
            ]);

            if ($ok) {
                $saveSystemState($pdo, $coreSettingsTable, ['last_backup' => $createdAt]);
                $appendCoreLog($pdo, $coreLogsTable, 'backup', 'info', 'Backup manuel cree', ['file' => $filename, 'size' => $size]);
            } else {
                $appendCoreLog($pdo, $coreLogsTable, 'backup', 'error', 'Echec creation backup manuel', ['file' => $filename]);
            }

            return $redirect($adminBase . '/maintenance', [
                'msg' => $ok ? 'Sauvegarde creee: ' . $filename : 'Echec creation sauvegarde.',
                'mt' => $ok ? 'success' : 'danger',
            ]);
        },
        'middleware' => [$authRequired, $csrfCheck],
    ],

    [
        'method' => 'GET',
        'path' => '/maintenance/backup/download',
        'handler' => static function (Request $request) use ($redirect, $coreBackupsTable): Response {
            $controller = new AuthController();
            $adminBase = $controller->adminBasePath();
            $pdo = (new ConnectionManager())->connection();
            $backup = trim((string) $request->input('backup', ''));
            if ($backup === '') {
                return $redirect($adminBase . '/maintenance', ['msg' => 'Backup invalide.', 'mt' => 'danger']);
            }

            $stmt = $pdo->prepare('SELECT file_path FROM ' . $coreBackupsTable . ' WHERE backup_type != \'restore\' ORDER BY created_at DESC');
            $stmt->execute();
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $rows = is_array($rows) ? $rows : [];

            $selected = null;
            foreach ($rows as $row) {
                $path = (string) ($row['file_path'] ?? '');
                if ($path !== '' && basename($path) === basename($backup)) {
                    $selected = $path;
                    break;
                }
            }

            if ($selected === null || !is_file($selected)) {
                return $redirect($adminBase . '/maintenance', ['msg' => 'Backup introuvable.', 'mt' => 'danger']);
            }

            $real = realpath($selected);
            $allowedRoot = realpath(CATMIN_STORAGE . '/backups');
            if (!is_string($real) || !is_string($allowedRoot) || !str_starts_with($real, $allowedRoot . '/')) {
                return $redirect($adminBase . '/maintenance', ['msg' => 'Chemin backup refuse.', 'mt' => 'danger']);
            }

            $content = (string) @file_get_contents($real);
            if ($content === '') {
                return $redirect($adminBase . '/maintenance', ['msg' => 'Backup vide ou illisible.', 'mt' => 'danger']);
            }

            $ext = strtolower((string) pathinfo($real, PATHINFO_EXTENSION));
            $mime = match ($ext) {
                'zip' => 'application/zip',
                'sql' => 'application/sql',
                'json' => 'application/json',
                default => 'application/octet-stream',
            };

            return Response::html($content, 200, [
                'Content-Type' => $mime,
                'Content-Disposition' => 'attachment; filename="' . basename($real) . '"',
                'Cache-Control' => 'no-store',
            ]);
        },
        'middleware' => [$authRequired],
    ],

    [
        'method' => 'GET',
        'path' => '/maintenance/backup/read',
        'handler' => static function (Request $request) use ($redirect, $coreBackupsTable): Response {
            $controller = new AuthController();
            $adminBase = $controller->adminBasePath();
            $pdo = (new ConnectionManager())->connection();
            $backup = trim((string) $request->input('backup', ''));
            if ($backup === '') {
                return $redirect($adminBase . '/maintenance', ['msg' => 'Backup invalide.', 'mt' => 'danger']);
            }

            $stmt = $pdo->prepare('SELECT file_path FROM ' . $coreBackupsTable . ' WHERE backup_type != \'restore\' ORDER BY created_at DESC');
            $stmt->execute();
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $rows = is_array($rows) ? $rows : [];

            $selected = null;
            foreach ($rows as $row) {
                $path = (string) ($row['file_path'] ?? '');
                if ($path !== '' && basename($path) === basename($backup)) {
                    $selected = $path;
                    break;
                }
            }

            if ($selected === null || !is_file($selected)) {
                return $redirect($adminBase . '/maintenance', ['msg' => 'Backup introuvable.', 'mt' => 'danger']);
            }

            $real = realpath($selected);
            $allowedRoot = realpath(CATMIN_STORAGE . '/backups');
            if (!is_string($real) || !is_string($allowedRoot) || !str_starts_with($real, $allowedRoot . '/')) {
                return $redirect($adminBase . '/maintenance', ['msg' => 'Chemin backup refuse.', 'mt' => 'danger']);
            }

            $ext = strtolower((string) pathinfo($real, PATHINFO_EXTENSION));
            $isText = in_array($ext, ['txt', 'json', 'log', 'md', 'csv', 'sql'], true);
            $raw = $isText ? ((string) @file_get_contents($real)) : '';
            $preview = $isText ? mb_substr($raw, 0, 250000) : ('Fichier binaire (' . strtoupper($ext !== '' ? $ext : 'unknown') . ').');

            return View::make('maintenance.read', [
                'adminBase' => $adminBase,
                'backupName' => basename($real),
                'backupPath' => $real,
                'backupSize' => (int) (@filesize($real) ?: 0),
                'previewText' => $preview,
                'isTextPreview' => $isText,
            ], 'admin');
        },
        'middleware' => [$authRequired],
    ],

    [
        'method' => 'POST',
        'path' => '/maintenance/backup/delete',
        'handler' => static function (Request $request) use ($redirect, $coreBackupsTable, $appendCoreLog, $coreLogsTable): Response {
            $controller = new AuthController();
            $adminBase = $controller->adminBasePath();
            $pdo = (new ConnectionManager())->connection();
            $backup = trim((string) $request->input('backup', ''));
            if ($backup === '') {
                return $redirect($adminBase . '/maintenance', ['msg' => 'Backup invalide.', 'mt' => 'danger']);
            }

            $stmt = $pdo->query('SELECT id, file_path FROM ' . $coreBackupsTable . ' WHERE backup_type != \'restore\' ORDER BY created_at DESC');
            $rows = $stmt !== false ? ($stmt->fetchAll(\PDO::FETCH_ASSOC) ?: []) : [];
            $targetId = 0;
            $targetPath = '';
            foreach ($rows as $row) {
                $path = (string) ($row['file_path'] ?? '');
                if ($path !== '' && basename($path) === basename($backup)) {
                    $targetId = (int) ($row['id'] ?? 0);
                    $targetPath = $path;
                    break;
                }
            }

            if ($targetId <= 0 || $targetPath === '') {
                return $redirect($adminBase . '/maintenance', ['msg' => 'Backup introuvable.', 'mt' => 'danger']);
            }

            $real = realpath($targetPath);
            $allowedRoot = realpath(CATMIN_STORAGE . '/backups');
            if (!is_string($real) || !is_string($allowedRoot) || !str_starts_with($real, $allowedRoot . '/')) {
                return $redirect($adminBase . '/maintenance', ['msg' => 'Chemin backup refuse.', 'mt' => 'danger']);
            }

            $fileDeleted = !is_file($real) || @unlink($real);
            $dbDeleted = false;
            if ($fileDeleted) {
                $del = $pdo->prepare('DELETE FROM ' . $coreBackupsTable . ' WHERE id = :id');
                $dbDeleted = $del->execute(['id' => $targetId]);
            }

            $ok = $fileDeleted && $dbDeleted;
            if ($ok) {
                $appendCoreLog($pdo, $coreLogsTable, 'backup', 'warning', 'Backup supprime', ['file' => basename($real)]);
            }

            return $redirect($adminBase . '/maintenance', [
                'msg' => $ok ? ('Backup supprime: ' . basename($real)) : 'Echec suppression backup.',
                'mt' => $ok ? 'success' : 'danger',
            ]);
        },
        'middleware' => [$authRequired, $csrfCheck],
    ],

    [
        'method' => 'POST',
        'path' => '/maintenance/restore',
        'handler' => static function (Request $request) use ($saveSystemState, $redirect, $coreSettingsTable, $coreBackupsTable, $appendCoreLog, $coreLogsTable): Response {
            $controller = new AuthController();
            $adminBase = $controller->adminBasePath();
            $pdo = (new ConnectionManager())->connection();
            $backup = trim((string) $request->input('backup', ''));
            if ($backup === '') {
                return $redirect($adminBase . '/maintenance', ['msg' => 'Backup invalide.', 'mt' => 'danger']);
            }

            $selectedPath = null;
            $stmt = $pdo->query('SELECT file_path FROM ' . $coreBackupsTable . ' ORDER BY created_at DESC');
            $rows = $stmt !== false ? ($stmt->fetchAll(\PDO::FETCH_ASSOC) ?: []) : [];
            foreach ($rows as $row) {
                $path = (string) ($row['file_path'] ?? '');
                if ($path !== '' && basename($path) === basename($backup)) {
                    $selectedPath = $path;
                    break;
                }
            }
            if ($selectedPath === null) {
                return $redirect($adminBase . '/maintenance', ['msg' => 'Backup introuvable en base.', 'mt' => 'danger']);
            }

            $restoreStamp = date('Y-m-d H:i:s') . ' (' . basename($selectedPath) . ')';
            $saveSystemState($pdo, $coreSettingsTable, ['last_restore' => $restoreStamp]);
            $insert = $pdo->prepare(
                'INSERT INTO ' . $coreBackupsTable . ' (backup_type, status, file_path, checksum, size_bytes, created_at) VALUES (:backup_type, :status, :file_path, :checksum, :size_bytes, :created_at)'
            );
            $insert->execute([
                'backup_type' => 'restore',
                'status' => 'simulated',
                'file_path' => $selectedPath,
                'checksum' => null,
                'size_bytes' => 0,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $appendCoreLog($pdo, $coreLogsTable, 'backup', 'warning', 'Restore simule execute', ['file' => basename($selectedPath)]);

            return $redirect($adminBase . '/maintenance', [
                'msg' => 'Restore simule sur backup: ' . basename($selectedPath),
                'mt' => 'warning',
            ]);
        },
        'middleware' => [$authRequired, $csrfCheck],
    ],

    [
        'method' => 'POST',
        'path' => '/modules/integrity/scan',
        'handler' => static function (Request $request) use ($redirect): Response {
            $controller = new AuthController();
            $adminBase = $controller->adminBasePath();
            $persist = (string) $request->input('persist', '1') === '1';
            $report = (new CoreModuleIntegrityScanner())->scanAll($persist);
            $summary = (array) ($report['summary'] ?? []);
            $invalid = (int) (($summary['invalid'] ?? 0) + ($summary['tampered'] ?? 0));
            $message = 'Scan intégrité terminé.';
            if ($invalid > 0) {
                $message .= ' Modules invalides: ' . $invalid;
            }
            return $redirect($adminBase . '/modules/status', [
                'msg' => $message,
                'mt' => $invalid > 0 ? 'warning' : 'success',
            ]);
        },
        'middleware' => [$authRequired, $csrfCheck],
    ],

    [
        'method' => 'POST',
        'path' => '/modules/toggle',
        'handler' => static function (Request $request) use ($toggleModuleState, $redirect): Response {
            $controller = new AuthController();
            $adminBase = $controller->adminBasePath();

            $scope = strtolower(trim((string) $request->input('scope', '')));
            $slug = strtolower(trim((string) $request->input('slug', '')));
            $target = (string) $request->input('target', '0') === '1';
            $returnTo = strtolower(trim((string) $request->input('return_to', 'manager')));

            $result = $toggleModuleState($scope, $slug, $target);
            $mt = ($result['ok'] ?? false) ? 'success' : 'danger';
            $path = $returnTo === 'status' ? '/modules/status' : '/modules';

            return $redirect($adminBase . $path, [
                'msg' => (string) ($result['message'] ?? 'Operation terminee'),
                'mt' => $mt,
            ]);
        },
        'middleware' => [$authRequired, $csrfCheck],
    ],

    [
        'method' => 'POST',
        'path' => '/modules/dependencies/resolve',
        'handler' => static function (Request $request) use ($resolveModuleDependencies, $redirect): Response {
            $controller = new AuthController();
            $adminBase = $controller->adminBasePath();

            $scope = strtolower(trim((string) $request->input('scope', '')));
            $slug = strtolower(trim((string) $request->input('slug', '')));
            $activateTarget = (string) $request->input('activate_target', '1') === '1';
            $returnTo = strtolower(trim((string) $request->input('return_to', 'manager')));

            $result = $resolveModuleDependencies($scope, $slug, $activateTarget);
            $path = $returnTo === 'status' ? '/modules/status' : '/modules';

            return $redirect($adminBase . $path, [
                'msg' => (string) ($result['message'] ?? 'Résolution dépendances terminée.'),
                'mt' => (bool) ($result['ok'] ?? false) ? 'success' : 'danger',
            ]);
        },
        'middleware' => [$authRequired, $csrfCheck],
    ],

    [
        'method' => 'POST',
        'path' => '/modules/snapshot/create',
        'handler' => static function (Request $request) use ($redirect): Response {
            $controller = new AuthController();
            $adminBase = $controller->adminBasePath();
            $scope = strtolower(trim((string) $request->input('scope', '')));
            $slug = strtolower(trim((string) $request->input('slug', '')));
            $type = strtolower(trim((string) $request->input('type', 'emergency')));
            $result = (new CoreModuleSnapshotManager())->create($scope, $slug, $type, 'manual-admin');
            return $redirect($adminBase . '/modules', [
                'msg' => (string) ($result['message'] ?? 'Snapshot terminé.'),
                'mt' => (bool) ($result['ok'] ?? false) ? 'success' : 'danger',
            ]);
        },
        'middleware' => [$authRequired, $csrfCheck],
    ],

    [
        'method' => 'POST',
        'path' => '/modules/rollback',
        'handler' => static function (Request $request) use ($redirect): Response {
            $controller = new AuthController();
            $adminBase = $controller->adminBasePath();
            $slug = strtolower(trim((string) $request->input('slug', '')));
            $snapshotId = trim((string) $request->input('snapshot_id', ''));
            $result = (new CoreModuleRollbackRunner())->rollback($slug, $snapshotId);
            return $redirect($adminBase . '/modules', [
                'msg' => (string) ($result['message'] ?? 'Rollback terminé.'),
                'mt' => (bool) ($result['ok'] ?? false) ? 'success' : 'danger',
            ]);
        },
        'middleware' => [$authRequired, $csrfCheck],
    ],

    [
        'method' => 'GET',
        'path' => '/modules/uninstall/confirm',
        'handler' => static function (Request $request): Response {
            $controller = new AuthController();
            $adminBase = $controller->adminBasePath();
            $scope = strtolower(trim((string) $request->input('scope', '')));
            $slug = strtolower(trim((string) $request->input('slug', '')));
            $preview = (new CoreModuleUninstaller())->preview($scope, $slug);
            $snapshots = (new CoreModuleSnapshotManager())->list($slug);

            return View::make('modules.uninstall-confirm', [
                'adminBase' => $adminBase,
                'preview' => $preview,
                'scope' => $scope,
                'slug' => $slug,
                'snapshots' => $snapshots,
            ], 'admin');
        },
        'middleware' => [$authRequired],
    ],

    [
        'method' => 'POST',
        'path' => '/modules/uninstall/run',
        'handler' => static function (Request $request) use ($redirect): Response {
            $controller = new AuthController();
            $adminBase = $controller->adminBasePath();
            $scope = strtolower(trim((string) $request->input('scope', '')));
            $slug = strtolower(trim((string) $request->input('slug', '')));
            $policy = strtolower(trim((string) $request->input('data_policy', 'keep_data')));
            $confirmed = (string) $request->input('confirm', '0') === '1';
            if (!$confirmed) {
                return $redirect($adminBase . '/modules/uninstall/confirm', [
                    'scope' => $scope,
                    'slug' => $slug,
                    'msg' => 'Confirmation requise.',
                    'mt' => 'danger',
                ]);
            }
            $result = (new CoreModuleUninstaller())->uninstall($scope, $slug, $policy);
            return $redirect($adminBase . '/modules', [
                'msg' => (string) ($result['message'] ?? 'Désinstallation terminée.'),
                'mt' => (bool) ($result['ok'] ?? false) ? 'success' : 'danger',
            ]);
        },
        'middleware' => [$authRequired, $csrfCheck],
    ],

    [
        'method' => 'POST',
        'path' => '/modules/market/install',
        'handler' => static function (Request $request) use ($redirect): Response {
            $controller = new AuthController();
            $adminBase = $controller->adminBasePath();

            $scope = strtolower(trim((string) $request->input('scope', '')));
            $slug = strtolower(trim((string) $request->input('slug', '')));
            $repositorySlug = strtolower(trim((string) $request->input('repository_slug', '')));
            if ($scope === '' || $slug === '') {
                return $redirect($adminBase . '/modules/market', [
                    'msg' => 'Module invalide.',
                    'mt' => 'danger',
                ]);
            }

            $catalog = (new CoreMarketEngine())->catalog();
            $items = is_array($catalog['items'] ?? null) ? $catalog['items'] : [];
            $target = null;
            foreach ($items as $item) {
                if (!is_array($item)) {
                    continue;
                }
                if (strtolower((string) ($item['scope'] ?? '')) === $scope
                    && strtolower((string) ($item['slug'] ?? '')) === $slug
                    && ($repositorySlug === '' || strtolower((string) ($item['repo_slug'] ?? '')) === $repositorySlug)) {
                    $target = $item;
                    break;
                }
            }

            if (!is_array($target)) {
                return $redirect($adminBase . '/modules/market', [
                    'msg' => 'Module introuvable dans le catalogue.',
                    'mt' => 'danger',
                ]);
            }

            $marketEngine = new CoreMarketEngine();
            $catalogItems = is_array($catalog['items'] ?? null) ? $catalog['items'] : [];

            $isActive = static function (string $depSlug): bool {
                $snapshot = (new CoreModuleLoader())->scan();
                foreach ((array) ($snapshot['modules'] ?? []) as $module) {
                    $mSlug = strtolower(trim((string) ($module['manifest']['slug'] ?? '')));
                    if ($mSlug === $depSlug) {
                        return (bool) ($module['enabled'] ?? false);
                    }
                }
                return false;
            };

            $findCatalogItem = static function (string $depSlug) use ($catalogItems, $target): ?array {
                $repoSlug = strtolower(trim((string) ($target['repo_slug'] ?? '')));
                $scope = strtolower(trim((string) ($target['scope'] ?? 'admin')));
                foreach ($catalogItems as $item) {
                    if (!is_array($item)) {
                        continue;
                    }
                    if (strtolower(trim((string) ($item['slug'] ?? ''))) === $depSlug
                        && strtolower(trim((string) ($item['scope'] ?? ''))) === $scope
                        && strtolower(trim((string) ($item['repo_slug'] ?? ''))) === $repoSlug) {
                        return $item;
                    }
                }
                foreach ($catalogItems as $item) {
                    if (is_array($item) && strtolower(trim((string) ($item['slug'] ?? ''))) === $depSlug) {
                        return $item;
                    }
                }
                return null;
            };

            $installedNow = [];
            $visiting = [];
            $installWithDeps = static function (array $item) use (&$installWithDeps, &$installedNow, &$visiting, $findCatalogItem, $isActive, $marketEngine): array {
                $slug = strtolower(trim((string) ($item['slug'] ?? '')));
                if ($slug === '') {
                    return ['ok' => false, 'message' => 'Slug module invalide'];
                }

                if (($visiting[$slug] ?? false) === true) {
                    return ['ok' => false, 'message' => 'Cycle de dependances detecte: ' . $slug];
                }

                if ($isActive($slug)) {
                    return ['ok' => true, 'message' => 'Module deja actif: ' . $slug];
                }

                $visiting[$slug] = true;
                $requires = (array) (($item['manifest']['dependencies']['requires'] ?? []));
                foreach ($requires as $dep) {
                    $depSlug = strtolower(trim((string) $dep));
                    if ($depSlug === '' || $isActive($depSlug)) {
                        continue;
                    }

                    $depItem = $findCatalogItem($depSlug);
                    if (!is_array($depItem)) {
                        unset($visiting[$slug]);
                        return ['ok' => false, 'message' => 'Dependance introuvable dans le market: ' . $depSlug];
                    }

                    $depInstall = $installWithDeps($depItem);
                    if (!(bool) ($depInstall['ok'] ?? false)) {
                        unset($visiting[$slug]);
                        return $depInstall;
                    }
                }

                $result = $marketEngine->install($item);
                unset($visiting[$slug]);
                if (!(bool) ($result['ok'] ?? false)) {
                    return $result;
                }

                $installedNow[] = $slug;
                return $result;
            };

            $result = $installWithDeps($target);

            $msg = (string) ($result['message'] ?? 'Opération terminée.');
            if ((bool) ($result['ok'] ?? false) && $installedNow !== []) {
                $msg .= ' | Queue install: ' . implode(' => ', array_values(array_unique($installedNow)));
            }

            return $redirect($adminBase . '/modules/market', [
                'msg' => $msg,
                'mt' => (bool) ($result['ok'] ?? false) ? 'success' : 'danger',
            ]);
        },
        'middleware' => [$authRequired, $csrfCheck],
    ],

    [
        'method' => 'GET',
        'path' => '/staff',
        'handler' => static function (Request $request) use ($usersTable, $rolesTable, $consumeFlash): Response {
            $controller = new AuthController();
            $adminBase = $controller->adminBasePath();
            $pdo = (new ConnectionManager())->connection();
            $flash = $consumeFlash();

            $search = trim((string) $request->input('q', ''));
            $role = trim((string) $request->input('role', ''));
            $status = trim((string) $request->input('status', ''));
            $sort = trim((string) $request->input('sort', 'created_at'));
            $dir = strtolower(trim((string) $request->input('dir', 'desc'))) === 'asc' ? 'ASC' : 'DESC';
            $page = max(1, (int) $request->input('page', 1));
            $perPage = 12;

            $sortable = [
                'username' => 'u.username',
                'email' => 'u.email',
                'created_at' => 'u.created_at',
                'last_login_at' => 'u.last_login_at',
                'role' => 'r.slug',
                'status' => 'u.is_active',
            ];
            $orderBy = $sortable[$sort] ?? 'u.created_at';

            $where = [];
            $params = [];

            if ($search !== '') {
                $where[] = '(u.username LIKE :search OR u.email LIKE :search)';
                $params['search'] = '%' . $search . '%';
            }
            if ($role !== '') {
                $where[] = 'r.slug = :role';
                $params['role'] = $role;
            }
            if ($status === 'active') {
                $where[] = 'u.is_active = 1';
            } elseif ($status === 'inactive') {
                $where[] = 'u.is_active = 0';
            }

            $whereSql = $where !== [] ? (' WHERE ' . implode(' AND ', $where)) : '';

            $countStmt = $pdo->prepare(
                'SELECT COUNT(*) FROM ' . $usersTable . ' u LEFT JOIN ' . $rolesTable . ' r ON r.id = u.role_id' . $whereSql
            );
            $countStmt->execute($params);
            $total = (int) ($countStmt->fetchColumn() ?: 0);
            $pages = max(1, (int) ceil($total / $perPage));
            $page = min($page, $pages);
            $offset = ($page - 1) * $perPage;

            $sql = 'SELECT u.id, u.role_id, u.username, u.email, u.is_active, u.last_login_at, u.created_at, u.updated_at, '
                . 'r.name AS role_name, r.slug AS role_slug, r.is_system AS role_is_system '
                . 'FROM ' . $usersTable . ' u '
                . 'LEFT JOIN ' . $rolesTable . ' r ON r.id = u.role_id'
                . $whereSql
                . ' ORDER BY ' . $orderBy . ' ' . $dir . ' LIMIT :limit OFFSET :offset';

            $stmt = $pdo->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue(':' . $key, $value, \PDO::PARAM_STR);
            }
            $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $rows = is_array($rows) ? $rows : [];

            $roleStmt = $pdo->query('SELECT id, name, slug FROM ' . $rolesTable . ' ORDER BY name ASC');
            $roleOptions = $roleStmt !== false ? ($roleStmt->fetchAll(\PDO::FETCH_ASSOC) ?: []) : [];

            return View::make('staff.index', [
                'adminBase' => $adminBase,
                'rows' => $rows,
                'roleOptions' => $roleOptions,
                'filters' => [
                    'q' => $search,
                    'role' => $role,
                    'status' => $status,
                    'sort' => $sort,
                    'dir' => strtolower($dir),
                    'page' => $page,
                ],
                'pagination' => [
                    'total' => $total,
                    'per_page' => $perPage,
                    'page' => $page,
                    'pages' => $pages,
                ],
                'message' => (string) ($flash['message'] ?? ''),
                'messageType' => (string) ($flash['type'] ?? 'success'),
            ], 'admin');
        },
        'middleware' => [$authRequired],
    ],
    [
        'method' => 'GET',
        'path' => '/staff/create',
        'handler' => static function () use ($rolesTable): Response {
            $controller = new AuthController();
            $adminBase = $controller->adminBasePath();
            $pdo = (new ConnectionManager())->connection();
            $roles = ($pdo->query('SELECT id, name, slug, is_system FROM ' . $rolesTable . ' ORDER BY is_system DESC, name ASC') ?: null);
            $roles = $roles !== null ? ($roles->fetchAll(\PDO::FETCH_ASSOC) ?: []) : [];

            return View::make('staff.create', [
                'adminBase' => $adminBase,
                'roles' => $roles,
                'values' => ['status' => '1'],
                'errors' => [],
            ], 'admin');
        },
        'middleware' => [$authRequired],
    ],
    [
        'method' => 'POST',
        'path' => '/staff/create',
        'handler' => static function (Request $request) use ($rolesTable, $usersTable, $redirect): Response {
            $controller = new AuthController();
            $adminBase = $controller->adminBasePath();
            $pdo = (new ConnectionManager())->connection();

            $username = trim((string) $request->input('username', ''));
            $email = trim((string) $request->input('email', ''));
            $roleId = (int) $request->input('role_id', 0);
            $password = (string) $request->input('password', '');
            $passwordConfirm = (string) $request->input('password_confirm', '');
            $status = (string) $request->input('is_active', '1') === '1' ? 1 : 0;

            $errors = [];
            if ($username === '') {
                $errors['username'] = 'Username requis.';
            }
            if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                $errors['email'] = 'Email invalide.';
            }
            if ($roleId <= 0) {
                $errors['role_id'] = 'Role requis.';
            }
            if (mb_strlen($password) < 12) {
                $errors['password'] = '12 caracteres minimum.';
            }
            if ($password !== $passwordConfirm) {
                $errors['password_confirm'] = 'Confirmation differente.';
            }

            $roles = ($pdo->query('SELECT id, name, slug, is_system FROM ' . $rolesTable . ' ORDER BY is_system DESC, name ASC') ?: null);
            $roles = $roles !== null ? ($roles->fetchAll(\PDO::FETCH_ASSOC) ?: []) : [];

            if ($errors !== []) {
                return View::make('staff.create', [
                    'adminBase' => $adminBase,
                    'roles' => $roles,
                    'values' => [
                        'username' => $username,
                        'email' => $email,
                        'role_id' => (string) $roleId,
                        'is_active' => (string) $status,
                    ],
                    'errors' => $errors,
                ], 'admin');
            }

            $stmt = $pdo->prepare(
                'INSERT INTO ' . $usersTable . ' (role_id, username, email, password_hash, is_active, created_at, updated_at) VALUES (:role_id, :username, :email, :password_hash, :is_active, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
            );
            $stmt->execute([
                'role_id' => $roleId,
                'username' => $username,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'is_active' => $status,
            ]);

            return $redirect($adminBase . '/staff', ['msg' => 'Compte cree.', 'mt' => 'success']);
        },
        'middleware' => [$authRequired, $csrfCheck],
    ],
    [
        'method' => 'GET',
        'path' => '/staff/{id}',
        'handler' => static function (string $id) use ($findStaffById, $eventsTable): Response {
            $controller = new AuthController();
            $adminBase = $controller->adminBasePath();
            $pdo = (new ConnectionManager())->connection();
            $staff = $findStaffById($pdo, (int) $id);
            if ($staff === null) {
                return Response::text('Not Found', 404);
            }

            $eventsStmt = $pdo->prepare('SELECT event_type, severity, created_at FROM ' . $eventsTable . ' WHERE user_id = :id ORDER BY created_at DESC LIMIT 12');
            $eventsStmt->execute(['id' => (int) $id]);
            $events = $eventsStmt->fetchAll(\PDO::FETCH_ASSOC);
            $events = is_array($events) ? $events : [];

            return View::make('staff.show', [
                'adminBase' => $adminBase,
                'staff' => $staff,
                'events' => $events,
                'message' => '',
                'messageType' => 'info',
            ], 'admin');
        },
        'middleware' => [$authRequired],
    ],
    [
        'method' => 'GET',
        'path' => '/staff/{id}/edit',
        'handler' => static function (string $id) use ($findStaffById, $rolesTable): Response {
            $controller = new AuthController();
            $adminBase = $controller->adminBasePath();
            $pdo = (new ConnectionManager())->connection();
            $staff = $findStaffById($pdo, (int) $id);
            if ($staff === null) {
                return Response::text('Not Found', 404);
            }
            $roles = ($pdo->query('SELECT id, name, slug, is_system FROM ' . $rolesTable . ' ORDER BY is_system DESC, name ASC') ?: null);
            $roles = $roles !== null ? ($roles->fetchAll(\PDO::FETCH_ASSOC) ?: []) : [];

            return View::make('staff.edit', [
                'adminBase' => $adminBase,
                'staff' => $staff,
                'roles' => $roles,
                'values' => [
                    'username' => (string) ($staff['username'] ?? ''),
                    'email' => (string) ($staff['email'] ?? ''),
                    'role_id' => (string) ($staff['role_id'] ?? ''),
                    'is_active' => (string) ((int) ($staff['is_active'] ?? 0)),
                ],
                'errors' => [],
            ], 'admin');
        },
        'middleware' => [$authRequired],
    ],
    [
        'method' => 'POST',
        'path' => '/staff/{id}/edit',
        'handler' => static function (Request $request, string $id) use ($findStaffById, $isSuperAdminUser, $rolesTable, $usersTable, $redirect): Response {
            $controller = new AuthController();
            $adminBase = $controller->adminBasePath();
            $pdo = (new ConnectionManager())->connection();
            $staff = $findStaffById($pdo, (int) $id);
            if ($staff === null) {
                return Response::text('Not Found', 404);
            }

            $username = trim((string) $request->input('username', ''));
            $email = trim((string) $request->input('email', ''));
            $roleId = (int) $request->input('role_id', 0);
            $status = (string) $request->input('is_active', '1') === '1' ? 1 : 0;

            $errors = [];
            if ($username === '') {
                $errors['username'] = 'Username requis.';
            }
            if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                $errors['email'] = 'Email invalide.';
            }
            if ($roleId <= 0) {
                $errors['role_id'] = 'Role requis.';
            }

            if ($isSuperAdminUser($staff)) {
                $status = 1;
                $roleId = (int) ($staff['role_id'] ?? 0);
            }

            $roles = ($pdo->query('SELECT id, name, slug, is_system FROM ' . $rolesTable . ' ORDER BY is_system DESC, name ASC') ?: null);
            $roles = $roles !== null ? ($roles->fetchAll(\PDO::FETCH_ASSOC) ?: []) : [];

            if ($errors !== []) {
                return View::make('staff.edit', [
                    'adminBase' => $adminBase,
                    'staff' => $staff,
                    'roles' => $roles,
                    'values' => [
                        'username' => $username,
                        'email' => $email,
                        'role_id' => (string) $roleId,
                        'is_active' => (string) $status,
                    ],
                    'errors' => $errors,
                ], 'admin');
            }

            $stmt = $pdo->prepare('UPDATE ' . $usersTable . ' SET role_id = :role_id, username = :username, email = :email, is_active = :is_active, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
            $stmt->execute([
                'role_id' => $roleId,
                'username' => $username,
                'email' => $email,
                'is_active' => $status,
                'id' => (int) $id,
            ]);

            return $redirect($adminBase . '/staff/' . (int) $id, ['msg' => 'Compte mis a jour.', 'mt' => 'success']);
        },
        'middleware' => [$authRequired, $csrfCheck],
    ],
    [
        'method' => 'POST',
        'path' => '/staff/{id}/disable',
        'handler' => static function (string $id) use ($findStaffById, $isSuperAdminUser, $usersTable, $redirect): Response {
            $controller = new AuthController();
            $adminBase = $controller->adminBasePath();
            $pdo = (new ConnectionManager())->connection();
            $staff = $findStaffById($pdo, (int) $id);
            if ($staff === null) {
                return Response::text('Not Found', 404);
            }
            if ($isSuperAdminUser($staff)) {
                return $redirect($adminBase . '/staff', ['msg' => 'SuperAdmin protege.', 'mt' => 'warning']);
            }
            $stmt = $pdo->prepare('UPDATE ' . $usersTable . ' SET is_active = 0, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
            $stmt->execute(['id' => (int) $id]);
            return $redirect($adminBase . '/staff', ['msg' => 'Compte desactive.', 'mt' => 'warning']);
        },
        'middleware' => [$authRequired, $csrfCheck],
    ],
    [
        'method' => 'POST',
        'path' => '/staff/{id}/enable',
        'handler' => static function (string $id) use ($usersTable, $redirect): Response {
            $controller = new AuthController();
            $adminBase = $controller->adminBasePath();
            $pdo = (new ConnectionManager())->connection();
            $stmt = $pdo->prepare('UPDATE ' . $usersTable . ' SET is_active = 1, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
            $stmt->execute(['id' => (int) $id]);
            return $redirect($adminBase . '/staff', ['msg' => 'Compte active.', 'mt' => 'success']);
        },
        'middleware' => [$authRequired, $csrfCheck],
    ],
    [
        'method' => 'POST',
        'path' => '/staff/{id}/password',
        'handler' => static function (Request $request, string $id) use ($findStaffById, $isSuperAdminUser, $usersTable, $redirect): Response {
            $controller = new AuthController();
            $adminBase = $controller->adminBasePath();
            $pdo = (new ConnectionManager())->connection();
            $staff = $findStaffById($pdo, (int) $id);
            if ($staff === null) {
                return Response::text('Not Found', 404);
            }
            if ($isSuperAdminUser($staff)) {
                return $redirect($adminBase . '/staff/' . (int) $id, ['msg' => 'Reset superadmin indisponible via UI.', 'mt' => 'warning']);
            }

            $password = (string) $request->input('password', '');
            $passwordConfirm = (string) $request->input('password_confirm', '');
            if (mb_strlen($password) < 12 || $password !== $passwordConfirm) {
                return $redirect($adminBase . '/staff/' . (int) $id, ['msg' => 'Mot de passe invalide (12+ et confirmation).', 'mt' => 'danger']);
            }

            $stmt = $pdo->prepare('UPDATE ' . $usersTable . ' SET password_hash = :password_hash, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
            $stmt->execute([
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'id' => (int) $id,
            ]);

            return $redirect($adminBase . '/staff/' . (int) $id, ['msg' => 'Mot de passe mis a jour.', 'mt' => 'success']);
        },
        'middleware' => [$authRequired, $csrfCheck, $recentPassword],
    ],
    [
        'method' => 'POST',
        'path' => '/staff/bulk',
        'handler' => static function (Request $request) use ($findStaffById, $isSuperAdminUser, $usersTable, $redirect): Response {
            $controller = new AuthController();
            $adminBase = $controller->adminBasePath();
            $pdo = (new ConnectionManager())->connection();

            $ids = $request->post()['ids'] ?? [];
            $ids = is_array($ids) ? array_values(array_unique(array_map('intval', $ids))) : [];
            $action = trim((string) $request->input('action', ''));
            $roleId = (int) $request->input('role_id', 0);

            if ($ids === [] || $action === '') {
                return $redirect($adminBase . '/staff', ['msg' => 'Aucune action bulk appliquee.', 'mt' => 'warning']);
            }

            $eligible = [];
            foreach ($ids as $id) {
                $staff = $findStaffById($pdo, $id);
                if (!is_array($staff)) {
                    continue;
                }
                if ($isSuperAdminUser($staff)) {
                    continue;
                }
                $eligible[] = $id;
            }

            if ($eligible === []) {
                return $redirect($adminBase . '/staff', ['msg' => 'Aucun compte eligible (SuperAdmin protege).', 'mt' => 'warning']);
            }

            $placeholders = implode(',', array_fill(0, count($eligible), '?'));
            if ($action === 'enable') {
                $stmt = $pdo->prepare('UPDATE ' . $usersTable . ' SET is_active = 1, updated_at = CURRENT_TIMESTAMP WHERE id IN (' . $placeholders . ')');
                $stmt->execute($eligible);
                return $redirect($adminBase . '/staff', ['msg' => 'Comptes actives.', 'mt' => 'success']);
            }
            if ($action === 'disable') {
                $stmt = $pdo->prepare('UPDATE ' . $usersTable . ' SET is_active = 0, updated_at = CURRENT_TIMESTAMP WHERE id IN (' . $placeholders . ')');
                $stmt->execute($eligible);
                return $redirect($adminBase . '/staff', ['msg' => 'Comptes desactives.', 'mt' => 'warning']);
            }
            if ($action === 'role' && $roleId > 0) {
                $params = array_merge([$roleId], $eligible);
                $stmt = $pdo->prepare('UPDATE ' . $usersTable . ' SET role_id = ?, updated_at = CURRENT_TIMESTAMP WHERE id IN (' . $placeholders . ')');
                $stmt->execute($params);
                return $redirect($adminBase . '/staff', ['msg' => 'Roles mis a jour.', 'mt' => 'success']);
            }

            return $redirect($adminBase . '/staff', ['msg' => 'Action bulk non supportee.', 'mt' => 'danger']);
        },
        'middleware' => [$authRequired, $csrfCheck],
    ],

    [
        'method' => 'GET',
        'path' => '/roles',
        'handler' => static function (Request $request) use ($rolesTable, $usersTable, $ensureSuperAdminPermissions, $consumeFlash): Response {
            $controller = new AuthController();
            $adminBase = $controller->adminBasePath();
            $pdo = (new ConnectionManager())->connection();
            $flash = $consumeFlash();
            $ensureSuperAdminPermissions($pdo);

            $sql = 'SELECT r.id, r.name, r.slug, r.is_system, COUNT(u.id) AS users_count '
                . 'FROM ' . $rolesTable . ' r '
                . 'LEFT JOIN ' . $usersTable . ' u ON u.role_id = r.id '
                . 'GROUP BY r.id, r.name, r.slug, r.is_system '
                . 'ORDER BY r.is_system DESC, users_count DESC, r.name ASC';
            $stmt = $pdo->query($sql);
            $rows = $stmt !== false ? ($stmt->fetchAll(\PDO::FETCH_ASSOC) ?: []) : [];

            return View::make('roles.index', [
                'adminBase' => $adminBase,
                'rows' => $rows,
                'message' => (string) ($flash['message'] ?? ''),
                'messageType' => (string) ($flash['type'] ?? 'success'),
            ], 'admin');
        },
        'middleware' => [$authRequired],
    ],
    [
        'method' => 'GET',
        'path' => '/roles/create',
        'handler' => static function () use ($permissionsTable, $buildPermissionsMatrix, $ensureSuperAdminPermissions): Response {
            $controller = new AuthController();
            $adminBase = $controller->adminBasePath();
            $pdo = (new ConnectionManager())->connection();
            $ensureSuperAdminPermissions($pdo);

            $permsStmt = $pdo->query('SELECT id, name, slug, description FROM ' . $permissionsTable . ' ORDER BY slug ASC');
            $permissions = $permsStmt !== false ? ($permsStmt->fetchAll(\PDO::FETCH_ASSOC) ?: []) : [];

            return View::make('roles.create', [
                'adminBase' => $adminBase,
                'values' => ['level' => 'standard'],
                'errors' => [],
                'selectedPermissions' => [],
                'permissionMatrix' => $buildPermissionsMatrix($permissions),
            ], 'admin');
        },
        'middleware' => [$authRequired],
    ],
    [
        'method' => 'POST',
        'path' => '/roles/create',
        'handler' => static function (Request $request) use ($rolesTable, $permissionsTable, $rolePermissionsTable, $buildPermissionsMatrix, $redirect, $ensureSuperAdminPermissions): Response {
            $controller = new AuthController();
            $adminBase = $controller->adminBasePath();
            $pdo = (new ConnectionManager())->connection();

            $name = trim((string) $request->input('name', ''));
            $slug = trim((string) $request->input('slug', ''));
            $description = trim((string) $request->input('description', ''));
            $level = trim((string) $request->input('level', 'standard'));
            $permissionIds = $request->post()['permissions'] ?? [];
            $permissionIds = is_array($permissionIds) ? array_values(array_unique(array_map('intval', $permissionIds))) : [];

            $errors = [];
            if ($name === '') {
                $errors['name'] = 'Nom requis.';
            }
            if ($slug === '' || preg_match('/^[a-z0-9-]+$/', $slug) !== 1) {
                $errors['slug'] = 'Slug invalide (a-z0-9-).';
            }

            $permsStmt = $pdo->query('SELECT id, name, slug, description FROM ' . $permissionsTable . ' ORDER BY slug ASC');
            $permissions = $permsStmt !== false ? ($permsStmt->fetchAll(\PDO::FETCH_ASSOC) ?: []) : [];

            if ($errors !== []) {
                return View::make('roles.create', [
                    'adminBase' => $adminBase,
                    'values' => ['name' => $name, 'slug' => $slug, 'description' => $description, 'level' => $level],
                    'errors' => $errors,
                    'selectedPermissions' => $permissionIds,
                    'permissionMatrix' => $buildPermissionsMatrix($permissions),
                ], 'admin');
            }

            $insert = $pdo->prepare('INSERT INTO ' . $rolesTable . ' (name, slug, is_system, created_at) VALUES (:name, :slug, :is_system, CURRENT_TIMESTAMP)');
            $insert->execute([
                'name' => $name,
                'slug' => $slug,
                'is_system' => $level === 'critical' ? 1 : 0,
            ]);
            $roleId = (int) $pdo->lastInsertId();

            if ($permissionIds !== []) {
                $attach = $pdo->prepare('INSERT INTO ' . $rolePermissionsTable . ' (role_id, permission_id) VALUES (:role_id, :permission_id)');
                foreach ($permissionIds as $permissionId) {
                    $attach->execute(['role_id' => $roleId, 'permission_id' => $permissionId]);
                }
            }
            $ensureSuperAdminPermissions($pdo);

            return $redirect($adminBase . '/roles', ['msg' => 'Role cree.', 'mt' => 'success']);
        },
        'middleware' => [$authRequired, $csrfCheck],
    ],
    [
        'method' => 'GET',
        'path' => '/roles/{id}',
        'handler' => static function (string $id) use ($rolesTable, $permissionsTable, $rolePermissionsTable, $usersTable, $ensureSuperAdminPermissions): Response {
            $controller = new AuthController();
            $adminBase = $controller->adminBasePath();
            $pdo = (new ConnectionManager())->connection();
            $ensureSuperAdminPermissions($pdo);

            $roleStmt = $pdo->prepare('SELECT id, name, slug, is_system, created_at FROM ' . $rolesTable . ' WHERE id = :id LIMIT 1');
            $roleStmt->execute(['id' => (int) $id]);
            $role = $roleStmt->fetch(\PDO::FETCH_ASSOC);
            if (!is_array($role)) {
                return Response::text('Not Found', 404);
            }

            $permStmt = $pdo->prepare(
                'SELECT p.id, p.name, p.slug, p.description FROM ' . $permissionsTable . ' p '
                . 'INNER JOIN ' . $rolePermissionsTable . ' rp ON rp.permission_id = p.id '
                . 'WHERE rp.role_id = :role_id ORDER BY p.slug ASC'
            );
            $permStmt->execute(['role_id' => (int) $id]);
            $activePermissions = $permStmt->fetchAll(\PDO::FETCH_ASSOC);
            $activePermissions = is_array($activePermissions) ? $activePermissions : [];

            $usersStmt = $pdo->prepare('SELECT id, username, email, is_active FROM ' . $usersTable . ' WHERE role_id = :role_id ORDER BY username ASC LIMIT 20');
            $usersStmt->execute(['role_id' => (int) $id]);
            $users = $usersStmt->fetchAll(\PDO::FETCH_ASSOC);
            $users = is_array($users) ? $users : [];

            return View::make('roles.show', [
                'adminBase' => $adminBase,
                'role' => $role,
                'activePermissions' => $activePermissions,
                'linkedUsers' => $users,
            ], 'admin');
        },
        'middleware' => [$authRequired],
    ],
    [
        'method' => 'GET',
        'path' => '/roles/{id}/edit',
        'handler' => static function (string $id) use ($rolesTable, $permissionsTable, $rolePermissionsTable, $buildPermissionsMatrix, $ensureSuperAdminPermissions): Response {
            $controller = new AuthController();
            $adminBase = $controller->adminBasePath();
            $pdo = (new ConnectionManager())->connection();
            $ensureSuperAdminPermissions($pdo);

            $roleStmt = $pdo->prepare('SELECT id, name, slug, is_system FROM ' . $rolesTable . ' WHERE id = :id LIMIT 1');
            $roleStmt->execute(['id' => (int) $id]);
            $role = $roleStmt->fetch(\PDO::FETCH_ASSOC);
            if (!is_array($role)) {
                return Response::text('Not Found', 404);
            }

            $permsStmt = $pdo->query('SELECT id, name, slug, description FROM ' . $permissionsTable . ' ORDER BY slug ASC');
            $permissions = $permsStmt !== false ? ($permsStmt->fetchAll(\PDO::FETCH_ASSOC) ?: []) : [];

            $activeStmt = $pdo->prepare('SELECT permission_id FROM ' . $rolePermissionsTable . ' WHERE role_id = :role_id');
            $activeStmt->execute(['role_id' => (int) $id]);
            $selectedPermissions = array_map('intval', $activeStmt->fetchAll(\PDO::FETCH_COLUMN));

            return View::make('roles.edit', [
                'adminBase' => $adminBase,
                'role' => $role,
                'values' => [
                    'name' => (string) ($role['name'] ?? ''),
                    'slug' => (string) ($role['slug'] ?? ''),
                    'description' => '',
                    'level' => ((int) ($role['is_system'] ?? 0)) === 1 ? 'critical' : 'standard',
                ],
                'errors' => [],
                'selectedPermissions' => $selectedPermissions,
                'permissionMatrix' => $buildPermissionsMatrix($permissions),
            ], 'admin');
        },
        'middleware' => [$authRequired],
    ],
    [
        'method' => 'POST',
        'path' => '/roles/{id}/edit',
        'handler' => static function (Request $request, string $id) use ($rolesTable, $permissionsTable, $rolePermissionsTable, $buildPermissionsMatrix, $isCriticalRole, $redirect, $ensureSuperAdminPermissions): Response {
            $controller = new AuthController();
            $adminBase = $controller->adminBasePath();
            $pdo = (new ConnectionManager())->connection();

            $roleStmt = $pdo->prepare('SELECT id, name, slug, is_system FROM ' . $rolesTable . ' WHERE id = :id LIMIT 1');
            $roleStmt->execute(['id' => (int) $id]);
            $role = $roleStmt->fetch(\PDO::FETCH_ASSOC);
            if (!is_array($role)) {
                return Response::text('Not Found', 404);
            }

            if ($isCriticalRole($role)) {
                return $redirect($adminBase . '/roles', ['msg' => 'Role systeme critique protege.', 'mt' => 'warning']);
            }

            $name = trim((string) $request->input('name', ''));
            $slug = trim((string) $request->input('slug', ''));
            $description = trim((string) $request->input('description', ''));
            $level = trim((string) $request->input('level', 'standard'));
            $permissionIds = $request->post()['permissions'] ?? [];
            $permissionIds = is_array($permissionIds) ? array_values(array_unique(array_map('intval', $permissionIds))) : [];

            $errors = [];
            if ($name === '') {
                $errors['name'] = 'Nom requis.';
            }
            if ($slug === '' || preg_match('/^[a-z0-9-]+$/', $slug) !== 1) {
                $errors['slug'] = 'Slug invalide (a-z0-9-).';
            }

            $permsStmt = $pdo->query('SELECT id, name, slug, description FROM ' . $permissionsTable . ' ORDER BY slug ASC');
            $permissions = $permsStmt !== false ? ($permsStmt->fetchAll(\PDO::FETCH_ASSOC) ?: []) : [];

            if ($errors !== []) {
                return View::make('roles.edit', [
                    'adminBase' => $adminBase,
                    'role' => $role,
                    'values' => ['name' => $name, 'slug' => $slug, 'description' => $description, 'level' => $level],
                    'errors' => $errors,
                    'selectedPermissions' => $permissionIds,
                    'permissionMatrix' => $buildPermissionsMatrix($permissions),
                ], 'admin');
            }

            $update = $pdo->prepare('UPDATE ' . $rolesTable . ' SET name = :name, slug = :slug, is_system = :is_system WHERE id = :id');
            $update->execute([
                'name' => $name,
                'slug' => $slug,
                'is_system' => $level === 'critical' ? 1 : 0,
                'id' => (int) $id,
            ]);

            $pdo->prepare('DELETE FROM ' . $rolePermissionsTable . ' WHERE role_id = :role_id')->execute(['role_id' => (int) $id]);
            if ($permissionIds !== []) {
                $attach = $pdo->prepare('INSERT INTO ' . $rolePermissionsTable . ' (role_id, permission_id) VALUES (:role_id, :permission_id)');
                foreach ($permissionIds as $permissionId) {
                    $attach->execute(['role_id' => (int) $id, 'permission_id' => $permissionId]);
                }
            }
            $ensureSuperAdminPermissions($pdo);

            return $redirect($adminBase . '/roles/' . (int) $id, ['msg' => 'Role mis a jour.', 'mt' => 'success']);
        },
        'middleware' => [$authRequired, $csrfCheck],
    ],

    [
        'method' => 'POST',
        'path' => '/roles/{id}/delete',
        'handler' => static function (string $id) use ($rolesTable, $rolePermissionsTable, $usersTable, $isCriticalRole, $redirect): Response {
            $controller = new AuthController();
            $adminBase = $controller->adminBasePath();
            $pdo = (new ConnectionManager())->connection();

            $roleStmt = $pdo->prepare('SELECT id, name, slug, is_system FROM ' . $rolesTable . ' WHERE id = :id LIMIT 1');
            $roleStmt->execute(['id' => (int) $id]);
            $role = $roleStmt->fetch(\PDO::FETCH_ASSOC);
            if (!is_array($role)) {
                return $redirect($adminBase . '/roles', ['msg' => 'Role introuvable.', 'mt' => 'danger']);
            }
            if ($isCriticalRole($role)) {
                return $redirect($adminBase . '/roles', ['msg' => 'SuperAdmin/role critique non supprimable.', 'mt' => 'warning']);
            }

            $countUsers = $pdo->prepare('SELECT COUNT(*) FROM ' . $usersTable . ' WHERE role_id = :role_id');
            $countUsers->execute(['role_id' => (int) $id]);
            $usedBy = (int) ($countUsers->fetchColumn() ?: 0);
            if ($usedBy > 0) {
                return $redirect($adminBase . '/roles', ['msg' => 'Role attribue a des comptes: suppression refusee.', 'mt' => 'danger']);
            }

            $pdo->prepare('DELETE FROM ' . $rolePermissionsTable . ' WHERE role_id = :role_id')->execute(['role_id' => (int) $id]);
            $ok = $pdo->prepare('DELETE FROM ' . $rolesTable . ' WHERE id = :id LIMIT 1')->execute(['id' => (int) $id]);

            return $redirect($adminBase . '/roles', [
                'msg' => $ok ? 'Role supprime.' : 'Echec suppression role.',
                'mt' => $ok ? 'success' : 'danger',
            ]);
        },
        'middleware' => [$authRequired, $csrfCheck],
    ],

    [
        'method' => 'POST',
        'path' => '/sensitive-check',
        'handler' => static fn (): Response => Response::json(['ok' => true, 'message' => 'Sensitive action allowed']),
        'middleware' => [$authRequired, $recentPassword, $csrfCheck],
    ],
];
