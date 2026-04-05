<?php

declare(strict_types=1);

namespace Install;

use Core\auth\PasswordHasher;
use Core\config\Config;
use Core\config\EnvManager;
use Core\config\RuntimeConfigLoader;
use Core\database\ConnectionManager;
use Core\database\MigrationRunner;
use Core\database\SeederRunner;
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

    public function saveStep(string $step, array $input): array
    {
        if ($this->lockManager->isLocked()) {
            return ['ok' => false, 'message' => 'Installer locked.', 'redirect_step' => null];
        }

        $context = $this->store->load();

        if (!$this->stateMachine->hasStep($step)) {
            return ['ok' => false, 'message' => 'Unknown step.', 'redirect_step' => $context->currentStep()];
        }

        if (!$this->stateMachine->canAccess($step, $context)) {
            return ['ok' => false, 'message' => 'Step skipping is not allowed.', 'redirect_step' => $this->stateMachine->firstPending($context)];
        }

        $definition = $this->loadStepDefinition($step);
        $validator = $definition['validate'] ?? null;

        if (!is_callable($validator)) {
            throw new RuntimeException('Invalid step validator: ' . $step);
        }

        $result = $validator($input, $context);
        if (!is_array($result) || !($result['ok'] ?? false)) {
            return [
                'ok' => false,
                'message' => (string) ($result['message'] ?? 'Validation error.'),
                'redirect_step' => $step,
            ];
        }

        $payload = is_array($result['data'] ?? null) ? $result['data'] : [];
        $context->setStepData($step, $payload);
        $context->markCompleted($step);

        if ($step === 'profile') {
            $modules = $this->profileResolver->resolve((string) ($payload['profile'] ?? 'recommended'), $payload['custom_modules'] ?? []);
            $context->setMeta('planned_modules', $this->modulePlanner->plan($modules));
        }

        if ($step === 'execution') {
            $execution = $this->executeInstallation($context);
            if (!$execution['ok']) {
                return [
                    'ok' => false,
                    'message' => (string) $execution['message'],
                    'redirect_step' => 'execution',
                ];
            }

            $context->setStepData('execution', $execution['data']);
        }

        if ($step === 'recovery_codes') {
            $codes = $this->generateRecoveryCodes();
            $context->setStepData('recovery_codes', ['codes' => $codes]);
            $this->storeRecoveryCodes($codes);
        }

        if ($step === 'report') {
            $reportPath = $this->reportManager->generate($context);
            $context->setMeta('report_path', $reportPath);
        }

        if ($step === 'lock') {
            $this->finalizeLock($context);
            return ['ok' => true, 'message' => 'Installer locked.', 'redirect_step' => 'lock'];
        }

        $next = $this->stateMachine->next($step);
        $context->setCurrentStep($next ?? $step);
        $this->store->save($context);

        return ['ok' => true, 'message' => 'Step saved.', 'redirect_step' => $next ?? $step];
    }

    public function firstAccessibleStep(): string
    {
        $context = $this->store->load();

        return $this->stateMachine->firstPending($context);
    }

    private function executeInstallation(InstallerContext $context): array
    {
        $db = is_array($context->data('database')) ? $context->data('database') : [];
        $identity = is_array($context->data('identity')) ? $context->data('identity') : [];
        $security = is_array($context->data('security')) ? $context->data('security') : [];
        $superadmin = is_array($context->data('superadmin')) ? $context->data('superadmin') : [];

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

            (new MigrationRunner($manager))->run($driver);
            (new SeederRunner($manager))->runBase($driver);

            $this->createOrUpdateSuperAdmin($pdo, $superadmin);
            $this->writeRuntimeConfiguration($identity, $security, $driver);
            $this->writeEnvFile($identity, $security, $db);

            return [
                'ok' => true,
                'message' => 'Execution completed.',
                'data' => [
                    'db_driver' => $driver,
                    'executed_at' => date('c'),
                ],
            ];
        } catch (\Throwable $exception) {
            return [
                'ok' => false,
                'message' => 'Execution failed: ' . $exception->getMessage(),
                'data' => [],
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
            'CATMIN_DB_SQLITE_PATH=' . (string) ($database['sqlite_path'] ?? base_path('storage/database.sqlite')),
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

        $this->store->clear();
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
                $base . 'database' => (string) ($db['sqlite_path'] ?? base_path('storage/database.sqlite')),
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
}
