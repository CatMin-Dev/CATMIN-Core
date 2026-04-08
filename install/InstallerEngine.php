<?php

declare(strict_types=1);

namespace Install;

use Core\auth\PasswordHasher;
use Core\config\Config;
use Core\config\EnvManager;
use Core\config\RuntimeConfigLoader;
use Core\database\ConnectionManager;
use PDO;
use RuntimeException;

final class InstallerEngine
{
    public function __construct(
        private readonly InstallerStateMachine $stateMachine = new InstallerStateMachine(),
        private readonly InstallerSessionStore $store = new InstallerSessionStore(),
        private readonly InstallerLockManager $lockManager = new InstallerLockManager(),
        private readonly InstallerReportManager $reportManager = new InstallerReportManager(),
        private readonly InstallerProfileResolver $profileResolver = new InstallerProfileResolver(),
        private readonly InstallerModulePlanner $modulePlanner = new InstallerModulePlanner()
    ) {}

    public function context(): InstallerContext
    {
        return $this->store->load();
    }

    public function isLocked(): bool
    {
        return $this->lockManager->isLocked();
    }

    public function resetProgress(): void
    {
        $this->store->clear();
    }

    public function saveStep(string $step, array $input): array
    {
        if ($this->lockManager->isLocked()) {
            return ['ok' => false, 'message' => 'Installer locked.', 'redirect_step' => null];
        }

        $context = $this->store->load();
        if ($context->state() === 'not_started') {
            $context->setState('in_progress');
        }

        if (!$this->stateMachine->hasStep($step)) {
            return ['ok' => false, 'message' => 'Unknown step.', 'redirect_step' => $context->currentStep()];
        }

        if (!$this->stateMachine->canAccess($step, $context)) {
            return ['ok' => false, 'message' => 'Step skipping is not allowed.', 'redirect_step' => $this->stateMachine->firstPending($context)];
        }

        if ($step === 'execution' && in_array('execution', $context->completed(), true)) {
            $executionData = is_array($context->data('execution')) ? $context->data('execution') : [];
            if (!empty($executionData['executed_at'])) {
                return ['ok' => false, 'message' => 'Execution deja effectuee pour cette session.', 'redirect_step' => 'execution'];
            }
        }

        $definition = $this->loadStepDefinition($step);
        $validator = $definition['validate'] ?? null;

        if (!is_callable($validator)) {
            throw new RuntimeException('Invalid step validator: ' . $step);
        }

        $result = $validator($input, $context);
        if (!is_array($result) || !($result['ok'] ?? false)) {
            $context->setState('failed');
            $this->store->save($context);
            return [
                'ok' => false,
                'message' => (string) ($result['message'] ?? 'Validation error.'),
                'redirect_step' => $step,
            ];
        }

        $payload = is_array($result['data'] ?? null) ? $result['data'] : [];

        if ($step === 'database') {
            $dbCheck = $this->verifyDatabasePayload($payload);
            if (!($dbCheck['ok'] ?? false)) {
                $context->setState('failed');
                $this->store->save($context);
                return [
                    'ok' => false,
                    'message' => (string) ($dbCheck['message'] ?? 'Database connection failed.'),
                    'redirect_step' => 'database',
                ];
            }
        }

        foreach ($this->stepsAfter($step) as $nextStep) {
            $context->clearStepData($nextStep);
            $context->unmarkCompleted($nextStep);
        }
        $context->clearMeta('planned_modules');
        $context->clearMeta('report_path');

        if ($step === 'profile') {
            $profile = (string) ($payload['profile'] ?? 'recommended');
            $phase = (string) ($payload['profile_phase'] ?? 'select');

            if ($profile === 'custom' && $phase !== 'modules') {
                $payload['profile_phase'] = 'modules';
                $context->setStepData('profile', $payload);
                $context->unmarkCompleted('profile');
                $context->setCurrentStep('profile');
                $context->setState('in_progress');
                $this->store->save($context);

                return ['ok' => true, 'message' => 'Step saved.', 'redirect_step' => 'profile'];
            }
        }

        $context->setStepData($step, $payload);
        $context->markCompleted($step);
        $context->setState('step_validated');

        if ($step === 'profile') {
            $modules = $this->profileResolver->resolve((string) ($payload['profile'] ?? 'recommended'), $payload['custom_modules'] ?? []);
            $context->setMeta('planned_modules', $this->modulePlanner->plan($modules));
        }

        if ($step === 'execution') {
            $context->setState('executing');
            $this->store->save($context);
            $execution = $this->executeInstallation($context);
            if (!$execution['ok']) {
                $context->setState('failed');
                $context->setStepData('execution', $execution['data'] ?? []);
                $this->store->save($context);
                return [
                    'ok' => false,
                    'message' => (string) $execution['message'],
                    'redirect_step' => 'execution',
                ];
            }

            $context->setStepData('execution', $execution['data']);
            $context->setState('step_validated');
        }

        if ($step === 'recovery_codes') {
            $codes = $this->generateRecoveryCodes();
            $context->setStepData('recovery_codes', ['codes' => $codes]);
            $this->storeRecoveryCodes($codes);
        }

        if ($step === 'report') {
            $reportPath = $this->reportManager->generate($context);
            $context->setMeta('report_path', $reportPath);
            $context->setState('completed');
        }

        if ($step === 'lock') {
            $this->finalizeLock($context);
            return ['ok' => true, 'message' => 'Installer locked.', 'redirect_step' => 'lock'];
        }

        $next = $this->stateMachine->next($step);
        $context->setCurrentStep($next ?? $step);
        if ($context->state() === 'failed') {
            $context->setState('in_progress');
        }
        $this->store->save($context);

        return ['ok' => true, 'message' => 'Step saved.', 'redirect_step' => $next ?? $step];
    }

    public function firstAccessibleStep(): string
    {
        $context = $this->store->load();

        return $this->stateMachine->firstPending($context);
    }

    public function testDatabaseConnection(array $input): array
    {
        $definition = $this->loadStepDefinition('database');
        $validator = $definition['validate'] ?? null;
        if (!is_callable($validator)) {
            return ['ok' => false, 'message' => 'Database validator unavailable.'];
        }

        $context = $this->store->load();
        $result = $validator($input, $context);
        if (!is_array($result) || !($result['ok'] ?? false)) {
            return ['ok' => false, 'message' => (string) ($result['message'] ?? 'Validation error.')];
        }

        $db = is_array($result['data'] ?? null) ? $result['data'] : [];
        return $this->verifyDatabasePayload($db);
    }

    private function stepsAfter(string $step): array
    {
        $index = array_search($step, InstallerStateMachine::STEPS, true);
        if (!is_int($index)) {
            return [];
        }

        return array_slice(InstallerStateMachine::STEPS, $index + 1);
    }

    private function executeInstallation(InstallerContext $context): array
    {
        $db = is_array($context->data('database')) ? $context->data('database') : [];
        $identity = is_array($context->data('identity')) ? $context->data('identity') : [];
        $security = is_array($context->data('security')) ? $context->data('security') : [];
        $system = is_array($context->data('system')) ? $context->data('system') : [];
        $superadmin = is_array($context->data('superadmin')) ? $context->data('superadmin') : [];
        $plannedModules = is_array($context->meta('planned_modules', [])) ? $context->meta('planned_modules', []) : [];

        $driver = (string) ($db['driver'] ?? 'sqlite');

        $connectionPatch = $this->buildConnectionPatch($driver, $db);
        $repo = Config::repository();
        $repo->setByPath('database.default', $driver);

        foreach ($connectionPatch as $path => $value) {
            $repo->setByPath($path, $value);
        }

        try {
            $manager = new ConnectionManager();
            $pdo = $manager->connection($driver);
            $this->ping($pdo, $driver);

            require_once CATMIN_CORE . '/db-upgrade-runner.php';
            $upgrade = (new \CoreDbUpgradeRunner())->run($driver);

            $this->createOrUpdateSuperAdmin($pdo, $superadmin);
            $this->persistInstallRecords($pdo, $identity, $security, $system, $plannedModules);
            $this->writeRuntimeConfiguration($identity, $security, $driver);
            $this->writeEnvFile($identity, $security, $db);

            return [
                'ok' => true,
                'message' => 'Execution completed.',
                'data' => [
                    'db_driver' => $driver,
                    'db_version' => (string) ($upgrade['db_version'] ?? '0.0.0-dev.0'),
                    'expected_db_version' => (string) ($upgrade['expected_db_version'] ?? '0.0.0-dev.0'),
                    'migrations_applied' => (int) ($upgrade['applied_count'] ?? 0),
                    'environment' => (string) config('app.env', 'production'),
                    'profile' => (string) (($context->data('profile')['profile'] ?? 'recommended')),
                    'modules_activated' => array_values(array_map(
                        static fn (array $m): string => (string) ($m['name'] ?? ''),
                        array_filter($plannedModules, static fn (mixed $m): bool => is_array($m) && !empty($m['enabled']))
                    )),
                    'warnings' => [],
                    'errors' => [],
                    'executed_at' => date('c'),
                ],
            ];
        } catch (\Throwable $exception) {
            return [
                'ok' => false,
                'message' => 'Execution failed: ' . $exception->getMessage(),
                'data' => [
                    'errors' => [$exception->getMessage()],
                    'warnings' => [],
                ],
            ];
        }
    }

    private function createOrUpdateSuperAdmin(PDO $pdo, array $superadmin): void
    {
        $username = (string) ($superadmin['username'] ?? 'superadmin');
        $email = (string) ($superadmin['email'] ?? 'admin@example.com');
        $password = (string) ($superadmin['password'] ?? '');

        if ($password === '') {
            throw new RuntimeException('SuperAdmin password missing.');
        }

        $prefix = (string) Config::get('database.prefixes.admin', 'admin_');
        $rolesTable = $prefix . 'roles';
        $usersTable = $prefix . 'users';

        $roleStmt = $pdo->prepare('SELECT id FROM ' . $rolesTable . ' WHERE slug = :slug LIMIT 1');
        $roleStmt->execute(['slug' => 'super-admin']);
        $roleId = (int) ($roleStmt->fetchColumn() ?: 0);

        if ($roleId <= 0) {
            throw new RuntimeException('SuperAdmin role not found.');
        }

        $hasher = new PasswordHasher();
        $hash = $hasher->hash($password);

        $findUser = $pdo->prepare('SELECT id FROM ' . $usersTable . ' WHERE email = :email OR username = :username LIMIT 1');
        $findUser->execute(['email' => $email, 'username' => $username]);
        $userId = $findUser->fetchColumn();

        if ($userId === false) {
            $insert = $pdo->prepare(
                'INSERT INTO ' . $usersTable . ' (role_id, username, email, password_hash, is_active, created_at, updated_at) VALUES (:role_id, :username, :email, :password_hash, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
            );
            $insert->execute([
                'role_id' => $roleId,
                'username' => $username,
                'email' => $email,
                'password_hash' => $hash,
            ]);

            return;
        }

        $update = $pdo->prepare(
            'UPDATE ' . $usersTable . ' SET role_id = :role_id, username = :username, email = :email, password_hash = :password_hash, is_active = 1, updated_at = CURRENT_TIMESTAMP WHERE id = :id'
        );
        $update->execute([
            'role_id' => $roleId,
            'username' => $username,
            'email' => $email,
            'password_hash' => $hash,
            'id' => (int) $userId,
        ]);
    }

    private function writeRuntimeConfiguration(array $identity, array $security, string $driver): void
    {
        $loader = new RuntimeConfigLoader(Config::repository(), new EnvManager());
        $loader->writeRuntimeConfig(CATMIN_STORAGE . '/config/runtime.json', [
            'app' => [
                'name' => (string) ($identity['app_name'] ?? 'CATMIN'),
                'url' => (string) ($identity['app_url'] ?? '/'),
            ],
            'security' => [
                'admin_path' => (string) ($security['admin_path'] ?? 'admin'),
                'ip_whitelist_enabled' => (bool) ($security['ip_whitelist_enabled'] ?? false),
                'ip_whitelist' => is_array($security['ip_whitelist'] ?? null) ? array_values(array_map('strval', $security['ip_whitelist'])) : [],
            ],
            'database' => [
                'default' => $driver,
            ],
        ]);
    }

    private function writeEnvFile(array $identity, array $security, array $database): void
    {
        $adminPath = (string) ($security['admin_path'] ?? 'admin');

        $lines = [
            'APP_ENV=production',
            'APP_NAME="' . addslashes((string) ($identity['app_name'] ?? 'CATMIN')) . '"',
            'APP_URL="' . addslashes((string) ($identity['app_url'] ?? '/')) . '"',
            'CATMIN_ADMIN_PATH=' . $adminPath,
            'CATMIN_DB_DRIVER=' . (string) ($database['driver'] ?? 'sqlite'),
            'CATMIN_DB_HOST=' . (string) ($database['host'] ?? '127.0.0.1'),
            'CATMIN_DB_PORT=' . (string) ($database['port'] ?? ''),
            'CATMIN_DB_NAME=' . (string) ($database['database'] ?? ''),
            'CATMIN_DB_USER=' . (string) ($database['username'] ?? ''),
            'CATMIN_DB_PASS=' . (string) ($database['password'] ?? ''),
            'CATMIN_DB_SQLITE_PATH=' . $this->resolveSqlitePath((string) ($database['sqlite_path'] ?? 'db/database.sqlite')),
        ];

        file_put_contents(CATMIN_ROOT . '/.env', implode("\n", $lines) . "\n", LOCK_EX);
    }

    private function finalizeLock(InstallerContext $context): void
    {
        $context->sanitizeSecrets();
        $reportPath = $this->reportManager->generate($context);

        $adminPath = (string) ($context->data('security')['admin_path'] ?? Config::get('security.admin_path', 'admin'));

        $this->lockManager->lock([
            'version' => (string) ((json_decode((string) file_get_contents(CATMIN_ROOT . '/version.json'), true)['version'] ?? 'unknown')),
            'report' => $reportPath,
            'admin_path' => $adminPath,
        ]);
        $context->setState('locked');

        $this->store->clear();
        $this->clearInstallSessionSnapshots();
    }

    private function storeRecoveryCodes(array $codes): void
    {
        $hashed = array_map(static fn (string $code): string => hash('sha256', $code), $codes);

        $path = CATMIN_STORAGE . '/install/recovery-codes.json';
        $dir = dirname($path);

        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        file_put_contents($path, json_encode([
            'generated_at' => date('c'),
            'codes' => $hashed,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    }

    private function generateRecoveryCodes(): array
    {
        $codes = [];

        for ($i = 0; $i < 10; $i++) {
            $codes[] = strtoupper(bin2hex(random_bytes(4))) . '-' . strtoupper(bin2hex(random_bytes(4)));
        }

        return $codes;
    }

    private function ping(PDO $pdo, string $driver): void
    {
        if ($driver === 'sqlsrv') {
            $pdo->query('SELECT 1');
            return;
        }

        $pdo->query('SELECT 1');
    }

    private function buildConnectionPatch(string $driver, array $db): array
    {
        $base = 'database.connections.' . $driver . '.';

        if ($driver === 'sqlite') {
            return [
                $base . 'driver' => 'sqlite',
                $base . 'database' => $this->resolveSqlitePath((string) ($db['sqlite_path'] ?? 'db/database.sqlite')),
            ];
        }

        return [
            $base . 'driver' => $driver,
            $base . 'host' => (string) ($db['host'] ?? '127.0.0.1'),
            $base . 'port' => (int) ($db['port'] ?? 0),
            $base . 'database' => (string) ($db['database'] ?? ''),
            $base . 'username' => (string) ($db['username'] ?? ''),
            $base . 'password' => (string) ($db['password'] ?? ''),
        ];
    }

    private function loadStepDefinition(string $step): array
    {
        $path = CATMIN_INSTALL . '/steps/' . $step . '.php';
        if (!is_file($path)) {
            throw new RuntimeException('Missing installer step definition: ' . $step);
        }

        $definition = require $path;

        return is_array($definition) ? $definition : [];
    }

    private function persistInstallRecords(PDO $pdo, array $identity, array $security, array $system, array $plannedModules): void
    {
        $prefixes = Config::get('database.prefixes', []);
        $core = is_array($prefixes) ? (string) ($prefixes['core'] ?? 'core_') : 'core_';

        $installTable = $core . 'install';
        $settingsTable = $core . 'settings';
        $modulesTable = $core . 'modules';
        $cronTasksTable = $core . 'cron_tasks';

        $version = 'unknown';
        if (is_file(CATMIN_ROOT . '/version.json')) {
            $decoded = json_decode((string) file_get_contents(CATMIN_ROOT . '/version.json'), true);
            if (is_array($decoded) && isset($decoded['version']) && is_string($decoded['version'])) {
                $version = $decoded['version'];
            }
        }

        $appUrl = (string) ($identity['app_url'] ?? '/');
        $host = (string) parse_url($appUrl, PHP_URL_HOST);
        if ($host === '') {
            $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
        }

        $consent = !empty($system['consent_tracking']) ? 1 : 0;

        $checkInstall = $pdo->prepare('SELECT id FROM ' . $installTable . ' ORDER BY id ASC LIMIT 1');
        $checkInstall->execute();
        $installId = $checkInstall->fetchColumn();

        if ($installId === false) {
            $insertInstall = $pdo->prepare(
                'INSERT INTO ' . $installTable . ' (install_uuid, instance_uuid, primary_domain, installed_version, consent_tracking, installed_at) VALUES (:install_uuid, :instance_uuid, :primary_domain, :installed_version, :consent_tracking, CURRENT_TIMESTAMP)'
            );
            $insertInstall->execute([
                'install_uuid' => bin2hex(random_bytes(16)),
                'instance_uuid' => bin2hex(random_bytes(16)),
                'primary_domain' => substr($host, 0, 191),
                'installed_version' => substr($version, 0, 64),
                'consent_tracking' => $consent,
            ]);
        } else {
            $updateInstall = $pdo->prepare(
                'UPDATE ' . $installTable . ' SET primary_domain = :primary_domain, installed_version = :installed_version, consent_tracking = :consent_tracking WHERE id = :id'
            );
            $updateInstall->execute([
                'id' => (int) $installId,
                'primary_domain' => substr($host, 0, 191),
                'installed_version' => substr($version, 0, 64),
                'consent_tracking' => $consent,
            ]);
        }

        $appName = (string) ($identity['app_name'] ?? 'CATMIN');
        $timezone = (string) ($system['timezone'] ?? 'UTC');
        $adminPath = (string) ($security['admin_path'] ?? 'admin');
        $appEnv = (string) Config::get('app.env', 'production');
        $cronEnabled = filter_var((string) env('CRON_ENABLED', '1'), FILTER_VALIDATE_BOOLEAN);
        $defaultFromEmailHost = preg_replace('/^www\./i', '', $host);
        if (!is_string($defaultFromEmailHost) || trim($defaultFromEmailHost) === '') {
            $defaultFromEmailHost = 'example.com';
        }
        $defaultFromEmail = 'noreply@' . $defaultFromEmailHost;

        $this->upsertCoreSetting($pdo, $settingsTable, 'app', 'name', $appName, true);
        $this->upsertCoreSetting($pdo, $settingsTable, 'app', 'url', $appUrl, true);
        $this->upsertCoreSetting($pdo, $settingsTable, 'system', 'timezone', $timezone, true);
        $this->upsertCoreSetting($pdo, $settingsTable, 'security', 'admin_path', $adminPath, false);
        $this->upsertCoreSetting($pdo, $settingsTable, 'security', 'ip_whitelist_enabled', !empty($security['ip_whitelist_enabled']) ? '1' : '0', false);
        $this->upsertCoreSetting($pdo, $settingsTable, 'security', 'ip_whitelist', json_encode($security['ip_whitelist'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]', false);

        $seedSettings = [
            ['general', 'app_name', $appName, true],
            ['general', 'app_env', $appEnv, false],
            ['general', 'timezone', $timezone, true],
            ['general', 'admin_path', $adminPath, false],

            ['security', 'session_minutes', '120', false],
            ['security', 'max_attempts', '5', false],
            ['security', 'password_min', '12', false],
            ['security', 'enforce_2fa', '0', false],

            ['email', 'enabled', '0', false],
            ['email', 'driver', 'smtp', false],
            ['email', 'from_name', $appName, false],
            ['email', 'from_email', $defaultFromEmail, false],
            ['email', 'host', '', false],
            ['email', 'port', '587', false],
            ['email', 'encryption', 'tls', false],
            ['email', 'username', '', false],

            ['ui', 'theme_default', 'corporate', false],
            ['ui', 'compact_sidebar', '1', false],
            ['ui', 'table_density', 'comfortable', false],
            ['ui', 'show_debug', '0', false],

            ['maintenance', 'enabled', '0', false],
            ['maintenance', 'level', '1', false],
            ['maintenance', 'reason', '', false],
            ['maintenance', 'message', 'Maintenance en cours', false],
            ['maintenance', 'allow_admin', '1', false],
            ['maintenance', 'allowed_ips', '', false],
            ['maintenance', 'allowed_admin_ids', '', false],
            ['maintenance', 'started_at', '', false],
            ['maintenance', 'enabled_by', '', false],
            ['maintenance', 'last_backup', '-', false],
            ['maintenance', 'last_restore', '-', false],

            ['system', 'cron_enabled', $cronEnabled ? '1' : '0', false],
            ['security', 'admin_reauth_required', config('security.admin_reauth_required', true) ? '1' : '0', false],
            ['security', 'reauth_ttl_seconds', (string) ((int) config('security.reauth_ttl_seconds', 900)), false],
            ['security', 'session_lifetime', (string) ((int) config('security.session_lifetime', 7200)), false],
            ['security', 'bind_session_fingerprint', config('security.bind_session_fingerprint', true) ? '1' : '0', false],
            ['security', 'csrf_rotate_on_validation', config('security.csrf_rotate_on_validation', true) ? '1' : '0', false],
            ['security', 'superadmin_email_reset', config('security.superadmin_email_reset', false) ? '1' : '0', false],
            ['security', 'admin_password_min', (string) ((int) config('security.admin_password_min', 12)), false],
            ['security', 'admin_password_require_upper', config('security.admin_password_require_upper', true) ? '1' : '0', false],
            ['security', 'admin_password_require_lower', config('security.admin_password_require_lower', true) ? '1' : '0', false],
            ['security', 'admin_password_require_digit', config('security.admin_password_require_digit', true) ? '1' : '0', false],
            ['security', 'admin_password_require_symbol', config('security.admin_password_require_symbol', true) ? '1' : '0', false],
            ['security', 'progressive_lockout', config('security.progressive_lockout', true) ? '1' : '0', false],
            ['security', 'lockout_window_minutes', (string) ((int) config('security.lockout_window_minutes', 30)), false],
            ['security', 'admin_noindex', config('security.admin_noindex', true) ? '1' : '0', false],
        ];

        foreach ($seedSettings as $seed) {
            [$category, $key, $value, $isPublic] = $seed;
            $this->upsertCoreSetting($pdo, $settingsTable, (string) $category, (string) $key, (string) $value, (bool) $isPublic);
        }

        $cronDir = CATMIN_ROOT . '/cron';
        if (!is_dir($cronDir)) {
            @mkdir($cronDir, 0775, true);
        }

        $defaultCronTasks = [
            ['name' => 'Core Cache Cleanup', 'script_path' => 'core/cron/core-cache-cleanup.php', 'schedule_expr' => '0 */6 * * *'],
            ['name' => 'Core Logs Rotate', 'script_path' => 'core/cron/core-logs-rotate.php', 'schedule_expr' => '10 2 * * *'],
            ['name' => 'Core Health Check', 'script_path' => 'core/cron/core-health-check.php', 'schedule_expr' => '*/15 * * * *'],
            ['name' => 'Core Backup', 'script_path' => 'core/cron/core-backup.php', 'schedule_expr' => '30 2 * * *'],
        ];

        foreach ($defaultCronTasks as $task) {
            $checkCron = $pdo->prepare('SELECT id FROM ' . $cronTasksTable . ' WHERE script_path = :script_path LIMIT 1');
            $checkCron->execute(['script_path' => (string) $task['script_path']]);
            $cronId = $checkCron->fetchColumn();

            if ($cronId === false) {
                $insertCron = $pdo->prepare(
                    'INSERT INTO ' . $cronTasksTable . ' (name, script_path, schedule_expr, is_active, created_at, updated_at) VALUES (:name, :script_path, :schedule_expr, :is_active, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
                );
                $insertCron->execute([
                    'name' => (string) $task['name'],
                    'script_path' => (string) $task['script_path'],
                    'schedule_expr' => (string) $task['schedule_expr'],
                    'is_active' => 0,
                ]);
                continue;
            }

            $updateCron = $pdo->prepare('UPDATE ' . $cronTasksTable . ' SET name = :name, schedule_expr = :schedule_expr, is_active = 0, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
            $updateCron->execute([
                'id' => (int) $cronId,
                'name' => (string) $task['name'],
                'schedule_expr' => (string) $task['schedule_expr'],
            ]);
        }

        foreach ($plannedModules as $module) {
            if (!is_array($module)) {
                continue;
            }
            $slug = strtolower(trim((string) ($module['name'] ?? '')));
            if ($slug === '') {
                continue;
            }

            $checkModule = $pdo->prepare('SELECT id FROM ' . $modulesTable . ' WHERE slug = :slug LIMIT 1');
            $checkModule->execute(['slug' => $slug]);
            $moduleId = $checkModule->fetchColumn();

            if ($moduleId === false) {
                $insertModule = $pdo->prepare(
                    'INSERT INTO ' . $modulesTable . ' (name, slug, version, status, installed_at, updated_at) VALUES (:name, :slug, :version, :status, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
                );
                $insertModule->execute([
                    'name' => $slug,
                    'slug' => $slug,
                    'version' => substr($version, 0, 64),
                    'status' => 'active',
                ]);
                continue;
            }

            $updateModule = $pdo->prepare('UPDATE ' . $modulesTable . ' SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
            $updateModule->execute([
                'id' => (int) $moduleId,
                'status' => 'active',
            ]);
        }
    }

    private function upsertCoreSetting(PDO $pdo, string $table, string $category, string $key, string $value, bool $isPublic): void
    {
        $find = $pdo->prepare('SELECT id FROM ' . $table . ' WHERE category = :category AND setting_key = :setting_key LIMIT 1');
        $find->execute([
            'category' => $category,
            'setting_key' => $key,
        ]);
        $id = $find->fetchColumn();

        if ($id === false) {
            $insert = $pdo->prepare(
                'INSERT INTO ' . $table . ' (category, setting_key, setting_value, is_public, updated_at) VALUES (:category, :setting_key, :setting_value, :is_public, CURRENT_TIMESTAMP)'
            );
            $insert->execute([
                'category' => $category,
                'setting_key' => $key,
                'setting_value' => $value,
                'is_public' => $isPublic ? 1 : 0,
            ]);
            return;
        }

        $update = $pdo->prepare(
            'UPDATE ' . $table . ' SET setting_value = :setting_value, is_public = :is_public, updated_at = CURRENT_TIMESTAMP WHERE id = :id'
        );
        $update->execute([
            'id' => (int) $id,
            'setting_value' => $value,
            'is_public' => $isPublic ? 1 : 0,
        ]);
    }

    private function clearInstallSessionSnapshots(): void
    {
        $sessionDir = CATMIN_STORAGE . '/install/sessions';
        if (!is_dir($sessionDir)) {
            return;
        }

        foreach (glob($sessionDir . '/*.json') ?: [] as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }

    private function resolveSqlitePath(string $path): string
    {
        $trimmed = trim($path);
        if ($trimmed === '') {
            return base_path('db/database.sqlite');
        }

        if (str_starts_with($trimmed, '/')) {
            return $trimmed;
        }

        return base_path(ltrim($trimmed, './'));
    }

    private function verifyDatabasePayload(array $db): array
    {
        $driver = (string) ($db['driver'] ?? 'sqlite');
        $connectionPatch = $this->buildConnectionPatch($driver, $db);

        $repo = Config::repository();
        $repo->setByPath('database.default', $driver);
        foreach ($connectionPatch as $path => $value) {
            $repo->setByPath($path, $value);
        }

        try {
            $manager = new ConnectionManager();
            $pdo = $manager->connection($driver);
            $this->ping($pdo, $driver);
            return ['ok' => true, 'message' => 'Connexion DB validée (' . $driver . ').'];
        } catch (\Throwable $exception) {
            return ['ok' => false, 'message' => 'Connexion DB échouée: ' . $exception->getMessage()];
        }
    }
}
