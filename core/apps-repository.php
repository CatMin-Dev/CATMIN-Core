<?php

declare(strict_types=1);

use Core\database\ConnectionManager;
use Core\database\SchemaBuilder;

require_once CATMIN_CORE . '/database/SchemaBuilder.php';

final class CoreAppsRepository
{
    private string $lastError = '';

    public function listEnabled(): array
    {
        $this->lastError = '';
        try {
            $manager = new ConnectionManager();
            $pdo = $manager->connection();
            $this->ensureTable($pdo, $manager->driver());
            $stmt = $pdo->query('SELECT id, label, icon, url, type, target, is_enabled, sort_order FROM ' . $this->table() . ' WHERE is_enabled = 1 ORDER BY sort_order ASC, id ASC');
            $rows = $stmt !== false ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
            return is_array($rows) ? $rows : [];
        } catch (\Throwable $exception) {
            $this->lastError = $exception->getMessage();
            \Core\logs\Logger::error('Apps repository listEnabled failed', ['error' => $this->lastError]);
            return [];
        }
    }

    public function listAll(): array
    {
        $this->lastError = '';
        try {
            $manager = new ConnectionManager();
            $pdo = $manager->connection();
            $this->ensureTable($pdo, $manager->driver());
            $stmt = $pdo->query('SELECT id, label, icon, url, type, target, is_enabled, sort_order, created_at, updated_at FROM ' . $this->table() . ' ORDER BY sort_order ASC, id ASC');
            $rows = $stmt !== false ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
            return is_array($rows) ? $rows : [];
        } catch (\Throwable $exception) {
            $this->lastError = $exception->getMessage();
            \Core\logs\Logger::error('Apps repository listAll failed', ['error' => $this->lastError]);
            return [];
        }
    }

    public function find(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $this->lastError = '';
        try {
            $manager = new ConnectionManager();
            $pdo = $manager->connection();
            $this->ensureTable($pdo, $manager->driver());
            $stmt = $pdo->prepare('SELECT id, label, icon, url, type, target, is_enabled, sort_order FROM ' . $this->table() . ' WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return is_array($row) ? $row : null;
        } catch (\Throwable $exception) {
            $this->lastError = $exception->getMessage();
            \Core\logs\Logger::error('Apps repository find failed', ['error' => $this->lastError, 'id' => $id]);
            return null;
        }
    }

    public function create(array $data): bool
    {
        $this->lastError = '';
        try {
            $manager = new ConnectionManager();
            $pdo = $manager->connection();
            $this->ensureTable($pdo, $manager->driver());
            $stmt = $pdo->prepare(
                'INSERT INTO ' . $this->table() . ' (label, icon, url, type, target, is_enabled, sort_order, created_at, updated_at) VALUES (:label, :icon, :url, :type, :target, :is_enabled, :sort_order, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
            );
            return $stmt->execute([
                'label' => (string) ($data['label'] ?? ''),
                'icon' => $data['icon'] ?? null,
                'url' => (string) ($data['url'] ?? ''),
                'type' => (string) ($data['type'] ?? 'external'),
                'target' => (string) ($data['target'] ?? '_blank'),
                'is_enabled' => !empty($data['is_enabled']) ? 1 : 0,
                'sort_order' => (int) ($data['sort_order'] ?? 100),
            ]);
        } catch (\Throwable $exception) {
            $this->lastError = $exception->getMessage();
            \Core\logs\Logger::error('Apps repository create failed', ['error' => $this->lastError]);
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
            $manager = new ConnectionManager();
            $pdo = $manager->connection();
            $this->ensureTable($pdo, $manager->driver());
            $stmt = $pdo->prepare(
                'UPDATE ' . $this->table() . ' SET label = :label, icon = :icon, url = :url, type = :type, target = :target, is_enabled = :is_enabled, sort_order = :sort_order, updated_at = CURRENT_TIMESTAMP WHERE id = :id'
            );
            return $stmt->execute([
                'id' => $id,
                'label' => (string) ($data['label'] ?? ''),
                'icon' => $data['icon'] ?? null,
                'url' => (string) ($data['url'] ?? ''),
                'type' => (string) ($data['type'] ?? 'external'),
                'target' => (string) ($data['target'] ?? '_blank'),
                'is_enabled' => !empty($data['is_enabled']) ? 1 : 0,
                'sort_order' => (int) ($data['sort_order'] ?? 100),
            ]);
        } catch (\Throwable $exception) {
            $this->lastError = $exception->getMessage();
            \Core\logs\Logger::error('Apps repository update failed', ['error' => $this->lastError, 'id' => $id]);
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
            $manager = new ConnectionManager();
            $pdo = $manager->connection();
            $this->ensureTable($pdo, $manager->driver());
            $stmt = $pdo->prepare('DELETE FROM ' . $this->table() . ' WHERE id = :id');
            return $stmt->execute(['id' => $id]);
        } catch (\Throwable $exception) {
            $this->lastError = $exception->getMessage();
            \Core\logs\Logger::error('Apps repository delete failed', ['error' => $this->lastError, 'id' => $id]);
            return false;
        }
    }

    public function lastError(): string
    {
        return $this->lastError;
    }

    private function table(): string
    {
        return (string) config('database.prefixes.core', 'core_') . 'apps';
    }

    private function ensureTable(\PDO $pdo, string $driver): void
    {
        if ($this->tableExists($pdo, $driver, $this->table())) {
            return;
        }

        $schema = new SchemaBuilder($pdo, $driver);
        $schema->create($this->table(), [
            ['name' => 'id', 'type' => 'bigint', 'primary' => true, 'auto_increment' => true],
            ['name' => 'label', 'type' => 'string', 'length' => 120],
            ['name' => 'icon', 'type' => 'string', 'length' => 255, 'nullable' => true],
            ['name' => 'url', 'type' => 'string', 'length' => 255],
            ['name' => 'type', 'type' => 'string', 'length' => 20, 'default' => 'external'],
            ['name' => 'target', 'type' => 'string', 'length' => 20, 'default' => '_blank'],
            ['name' => 'is_enabled', 'type' => 'boolean', 'default' => true],
            ['name' => 'sort_order', 'type' => 'integer', 'default' => 100],
            ['name' => 'created_at', 'type' => 'datetime', 'default' => 'CURRENT_TIMESTAMP'],
            ['name' => 'updated_at', 'type' => 'datetime', 'nullable' => true],
        ], [
            ['name' => 'ix_core_apps_enabled', 'columns' => ['is_enabled']],
            ['name' => 'ix_core_apps_sort', 'columns' => ['sort_order']],
        ]);
    }

    private function tableExists(\PDO $pdo, string $driver, string $table): bool
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
            if ($driver === 'sqlsrv') {
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM sys.tables WHERE name = :name');
                $stmt->execute(['name' => $table]);
                return (int) ($stmt->fetchColumn() ?: 0) > 0;
            }
        } catch (\Throwable) {
            return false;
        }

        return false;
    }
}
