<?php

declare(strict_types=1);

use Core\database\ConnectionManager;
use Core\database\SchemaBuilder;

require_once CATMIN_CORE . '/database/SchemaBuilder.php';

final class CoreModuleRepositoryRepository
{
    private string $lastError = '';

    public function listAll(): array
    {
        $this->lastError = '';
        try {
            [$pdo] = $this->bootstrap();
            $stmt = $pdo->query('SELECT * FROM ' . $this->repoTable() . ' ORDER BY is_official DESC, trust_level ASC, name ASC');
            $rows = $stmt !== false ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
            return is_array($rows) ? $rows : [];
        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
            return [];
        }
    }

    public function listEnabled(): array
    {
        $this->lastError = '';
        try {
            [$pdo] = $this->bootstrap();
            $stmt = $pdo->query('SELECT * FROM ' . $this->repoTable() . " WHERE is_enabled = 1 AND trust_level <> 'blocked' ORDER BY is_official DESC, trust_level ASC, name ASC");
            $rows = $stmt !== false ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
            return is_array($rows) ? $rows : [];
        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
            return [];
        }
    }

    public function findById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $this->lastError = '';
        try {
            [$pdo] = $this->bootstrap();
            $stmt = $pdo->prepare('SELECT * FROM ' . $this->repoTable() . ' WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return is_array($row) ? $row : null;
        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
            return null;
        }
    }

    public function findBySlug(string $slug): ?array
    {
        $slug = strtolower(trim($slug));
        if ($slug === '') {
            return null;
        }
        $this->lastError = '';
        try {
            [$pdo] = $this->bootstrap();
            $stmt = $pdo->prepare('SELECT * FROM ' . $this->repoTable() . ' WHERE slug = :slug LIMIT 1');
            $stmt->execute(['slug' => $slug]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return is_array($row) ? $row : null;
        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
            return null;
        }
    }

    public function create(array $data): bool
    {
        $this->lastError = '';
        try {
            [$pdo] = $this->bootstrap();
            $sql = 'INSERT INTO ' . $this->repoTable() . ' (name, slug, provider, repo_url, api_url, index_url, branch_or_channel, trust_level, is_official, is_enabled, requires_signature, requires_checksums, requires_manifest_standard, allowed_release_channels, notes, last_check_status, created_at, updated_at) VALUES (:name,:slug,:provider,:repo_url,:api_url,:index_url,:branch_or_channel,:trust_level,:is_official,:is_enabled,:requires_signature,:requires_checksums,:requires_manifest_standard,:allowed_release_channels,:notes,:last_check_status,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)';
            $stmt = $pdo->prepare($sql);
            return $stmt->execute([
                'name' => (string) ($data['name'] ?? ''),
                'slug' => (string) ($data['slug'] ?? ''),
                'provider' => (string) ($data['provider'] ?? 'github'),
                'repo_url' => (string) ($data['repo_url'] ?? ''),
                'api_url' => $data['api_url'] ?? null,
                'index_url' => $data['index_url'] ?? null,
                'branch_or_channel' => (string) ($data['branch_or_channel'] ?? 'main'),
                'trust_level' => (string) ($data['trust_level'] ?? 'community'),
                'is_official' => !empty($data['is_official']) ? 1 : 0,
                'is_enabled' => !empty($data['is_enabled']) ? 1 : 0,
                'requires_signature' => !empty($data['requires_signature']) ? 1 : 0,
                'requires_checksums' => !empty($data['requires_checksums']) ? 1 : 0,
                'requires_manifest_standard' => !array_key_exists('requires_manifest_standard', $data) || !empty($data['requires_manifest_standard']) ? 1 : 0,
                'allowed_release_channels' => (string) ($data['allowed_release_channels'] ?? 'stable,beta,dev'),
                'notes' => $data['notes'] ?? null,
                'last_check_status' => (string) ($data['last_check_status'] ?? 'never'),
            ]);
        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    public function update(int $id, array $data): bool
    {
        if ($id <= 0) {
            return false;
        }
        $this->lastError = '';
        try {
            [$pdo] = $this->bootstrap();
            $sql = 'UPDATE ' . $this->repoTable() . ' SET name = :name, slug = :slug, provider = :provider, repo_url = :repo_url, api_url = :api_url, index_url = :index_url, branch_or_channel = :branch_or_channel, trust_level = :trust_level, is_official = :is_official, is_enabled = :is_enabled, requires_signature = :requires_signature, requires_checksums = :requires_checksums, requires_manifest_standard = :requires_manifest_standard, allowed_release_channels = :allowed_release_channels, notes = :notes, updated_at = CURRENT_TIMESTAMP WHERE id = :id';
            $stmt = $pdo->prepare($sql);
            return $stmt->execute([
                'id' => $id,
                'name' => (string) ($data['name'] ?? ''),
                'slug' => (string) ($data['slug'] ?? ''),
                'provider' => (string) ($data['provider'] ?? 'github'),
                'repo_url' => (string) ($data['repo_url'] ?? ''),
                'api_url' => $data['api_url'] ?? null,
                'index_url' => $data['index_url'] ?? null,
                'branch_or_channel' => (string) ($data['branch_or_channel'] ?? 'main'),
                'trust_level' => (string) ($data['trust_level'] ?? 'community'),
                'is_official' => !empty($data['is_official']) ? 1 : 0,
                'is_enabled' => !empty($data['is_enabled']) ? 1 : 0,
                'requires_signature' => !empty($data['requires_signature']) ? 1 : 0,
                'requires_checksums' => !empty($data['requires_checksums']) ? 1 : 0,
                'requires_manifest_standard' => !array_key_exists('requires_manifest_standard', $data) || !empty($data['requires_manifest_standard']) ? 1 : 0,
                'allowed_release_channels' => (string) ($data['allowed_release_channels'] ?? 'stable,beta,dev'),
                'notes' => $data['notes'] ?? null,
            ]);
        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    public function delete(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }
        $this->lastError = '';
        try {
            [$pdo] = $this->bootstrap();
            $stmt = $pdo->prepare('DELETE FROM ' . $this->repoTable() . ' WHERE id = :id');
            return $stmt->execute(['id' => $id]);
        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    public function setEnabled(int $id, bool $enabled): bool
    {
        if ($id <= 0) {
            return false;
        }
        $this->lastError = '';
        try {
            [$pdo] = $this->bootstrap();
            $stmt = $pdo->prepare('UPDATE ' . $this->repoTable() . ' SET is_enabled = :is_enabled, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
            return $stmt->execute(['id' => $id, 'is_enabled' => $enabled ? 1 : 0]);
        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    public function updateCheckStatus(int $id, string $status, string $message): bool
    {
        if ($id <= 0) {
            return false;
        }
        $this->lastError = '';
        try {
            [$pdo] = $this->bootstrap();
            $stmt = $pdo->prepare('UPDATE ' . $this->repoTable() . ' SET last_check_status = :status, last_check_message = :message, last_check_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
            return $stmt->execute([
                'id' => $id,
                'status' => mb_substr(trim($status), 0, 40),
                'message' => $message !== '' ? mb_substr($message, 0, 4000) : null,
            ]);
        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    public function loadPolicy(): array
    {
        $defaults = $this->policyDefaults();
        $this->lastError = '';
        try {
            [$pdo] = $this->bootstrap();
            $stmt = $pdo->query('SELECT policy_key, policy_value FROM ' . $this->policyTable());
            $rows = $stmt !== false ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
            if (!is_array($rows)) {
                return $defaults;
            }
            foreach ($rows as $row) {
                $key = (string) ($row['policy_key'] ?? '');
                if ($key === '' || !array_key_exists($key, $defaults)) {
                    continue;
                }
                $defaults[$key] = in_array(strtolower((string) ($row['policy_value'] ?? '0')), ['1', 'true', 'yes', 'on'], true);
            }
            return $defaults;
        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
            return $defaults;
        }
    }

    public function savePolicy(array $policy): bool
    {
        $this->lastError = '';
        try {
            [$pdo] = $this->bootstrap();
            $defaults = $this->policyDefaults();
            $sqlSelect = 'SELECT id FROM ' . $this->policyTable() . ' WHERE policy_key = :policy_key LIMIT 1';
            $sqlInsert = 'INSERT INTO ' . $this->policyTable() . ' (policy_key, policy_value, updated_at) VALUES (:policy_key, :policy_value, CURRENT_TIMESTAMP)';
            $sqlUpdate = 'UPDATE ' . $this->policyTable() . ' SET policy_value = :policy_value, updated_at = CURRENT_TIMESTAMP WHERE id = :id';
            $select = $pdo->prepare($sqlSelect);
            $insert = $pdo->prepare($sqlInsert);
            $update = $pdo->prepare($sqlUpdate);

            foreach ($defaults as $key => $defaultValue) {
                $value = !empty($policy[$key]);
                $select->execute(['policy_key' => $key]);
                $id = $select->fetchColumn();
                if ($id === false) {
                    $ok = $insert->execute(['policy_key' => $key, 'policy_value' => $value ? '1' : '0']);
                } else {
                    $ok = $update->execute(['id' => (int) $id, 'policy_value' => $value ? '1' : '0']);
                }
                if (!$ok) {
                    return false;
                }
            }

            return true;
        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    public function lastError(): string
    {
        return $this->lastError;
    }

    private function bootstrap(): array
    {
        $manager = new ConnectionManager();
        $pdo = $manager->connection();
        $driver = $manager->driver();
        $this->ensureTables($pdo, $driver);
        $this->ensureOfficialDefault($pdo);

        return [$pdo, $driver];
    }

    private function repoTable(): string
    {
        return (string) config('database.prefixes.core', 'core_') . 'module_repositories';
    }

    private function policyTable(): string
    {
        return (string) config('database.prefixes.core', 'core_') . 'market_policy';
    }

    private function ensureTables(PDO $pdo, string $driver): void
    {
        $schema = new SchemaBuilder($pdo, $driver);

        if (!$this->tableExists($pdo, $driver, $this->repoTable())) {
            $schema->create($this->repoTable(), [
                ['name' => 'id', 'type' => 'bigint', 'primary' => true, 'auto_increment' => true],
                ['name' => 'name', 'type' => 'string', 'length' => 160],
                ['name' => 'slug', 'type' => 'string', 'length' => 120],
                ['name' => 'provider', 'type' => 'string', 'length' => 40, 'default' => 'github'],
                ['name' => 'repo_url', 'type' => 'string', 'length' => 255],
                ['name' => 'api_url', 'type' => 'string', 'length' => 255, 'nullable' => true],
                ['name' => 'index_url', 'type' => 'string', 'length' => 255, 'nullable' => true],
                ['name' => 'branch_or_channel', 'type' => 'string', 'length' => 80, 'default' => 'main'],
                ['name' => 'trust_level', 'type' => 'string', 'length' => 30, 'default' => 'community'],
                ['name' => 'is_official', 'type' => 'boolean', 'default' => false],
                ['name' => 'is_enabled', 'type' => 'boolean', 'default' => true],
                ['name' => 'requires_signature', 'type' => 'boolean', 'default' => false],
                ['name' => 'requires_checksums', 'type' => 'boolean', 'default' => false],
                ['name' => 'requires_manifest_standard', 'type' => 'boolean', 'default' => true],
                ['name' => 'allowed_release_channels', 'type' => 'string', 'length' => 120, 'default' => 'stable,beta,dev'],
                ['name' => 'notes', 'type' => 'text', 'nullable' => true],
                ['name' => 'last_check_at', 'type' => 'datetime', 'nullable' => true],
                ['name' => 'last_check_status', 'type' => 'string', 'length' => 40, 'default' => 'never'],
                ['name' => 'last_check_message', 'type' => 'text', 'nullable' => true],
                ['name' => 'created_at', 'type' => 'datetime', 'default' => 'CURRENT_TIMESTAMP'],
                ['name' => 'updated_at', 'type' => 'datetime', 'nullable' => true],
            ], [
                ['name' => 'ux_core_module_repositories_slug', 'columns' => ['slug'], 'unique' => true],
                ['name' => 'ix_core_module_repositories_enabled', 'columns' => ['is_enabled']],
                ['name' => 'ix_core_module_repositories_trust', 'columns' => ['trust_level']],
            ]);
        }

        if (!$this->tableExists($pdo, $driver, $this->policyTable())) {
            $schema->create($this->policyTable(), [
                ['name' => 'id', 'type' => 'bigint', 'primary' => true, 'auto_increment' => true],
                ['name' => 'policy_key', 'type' => 'string', 'length' => 120],
                ['name' => 'policy_value', 'type' => 'string', 'length' => 120],
                ['name' => 'updated_at', 'type' => 'datetime', 'nullable' => true],
            ], [
                ['name' => 'ux_core_market_policy_key', 'columns' => ['policy_key'], 'unique' => true],
            ]);
        }
    }

    private function ensureOfficialDefault(PDO $pdo): void
    {
        $slug = 'catmin-official-modules';
        $stmt = $pdo->prepare('SELECT id FROM ' . $this->repoTable() . ' WHERE slug = :slug LIMIT 1');
        $stmt->execute(['slug' => $slug]);
        if ($stmt->fetchColumn() !== false) {
            return;
        }

        $repo = trim((string) env('CATMIN_PUBLIC_MODULES_REPO', 'CatMin-Dev/CATMIN-Modules'));
        if ($repo === '') {
            $repo = 'CatMin-Dev/CATMIN-Modules';
        }

        $insert = $pdo->prepare('INSERT INTO ' . $this->repoTable() . ' (name, slug, provider, repo_url, api_url, index_url, branch_or_channel, trust_level, is_official, is_enabled, requires_signature, requires_checksums, requires_manifest_standard, allowed_release_channels, notes, last_check_status, created_at, updated_at) VALUES (:name,:slug,:provider,:repo_url,:api_url,:index_url,:branch_or_channel,:trust_level,:is_official,:is_enabled,:requires_signature,:requires_checksums,:requires_manifest_standard,:allowed_release_channels,:notes,:last_check_status,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)');
        $insert->execute([
            'name' => 'CATMIN Official Modules',
            'slug' => $slug,
            'provider' => 'github',
            'repo_url' => $repo,
            'api_url' => 'https://api.github.com/repos/' . $repo,
            'index_url' => null,
            'branch_or_channel' => 'main',
            'trust_level' => 'official',
            'is_official' => 1,
            'is_enabled' => 1,
            'requires_signature' => 0,
            'requires_checksums' => 0,
            'requires_manifest_standard' => 1,
            'allowed_release_channels' => 'stable,beta,dev',
            'notes' => 'Dépôt officiel CATMIN modules',
            'last_check_status' => 'never',
        ]);
    }

    /** @return array<string,bool> */
    private function policyDefaults(): array
    {
        return [
            'allow_official' => true,
            'allow_trusted' => true,
            'allow_community' => false,
            'require_checksums_official' => false,
            'require_checksums_trusted' => false,
            'require_checksums_community' => true,
            'require_checksums_all' => false,
            'require_signature_official' => false,
            'require_signature_trusted' => false,
            'require_signature_community' => true,
            'require_signature_all' => false,
            'hide_unverified_modules' => false,
            'show_community_by_default' => false,
            'allow_channel_stable' => true,
            'allow_channel_beta' => true,
            'allow_channel_alpha' => false,
            'allow_channel_experimental' => false,
            'allow_install_deprecated' => true,
            'allow_install_abandoned' => false,
            'hide_archived_modules' => true,
        ];
    }

    private function tableExists(PDO $pdo, string $driver, string $table): bool
    {
        try {
            if ($driver === 'sqlite') {
                $stmt = $pdo->prepare("SELECT name FROM sqlite_master WHERE type = 'table' AND name = :name LIMIT 1");
                $stmt->execute(['name' => $table]);
                return is_string($stmt->fetchColumn());
            }
            if ($driver === 'mysql') {
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :name');
                $stmt->execute(['name' => $table]);
                return (int) ($stmt->fetchColumn() ?: 0) > 0;
            }
            if ($driver === 'pgsql') {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = current_schema() AND table_name = :name");
                $stmt->execute(['name' => $table]);
                return (int) ($stmt->fetchColumn() ?: 0) > 0;
            }
        } catch (\Throwable) {
            return false;
        }

        return false;
    }
}
