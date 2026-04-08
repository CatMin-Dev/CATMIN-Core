<?php

declare(strict_types=1);

use Core\database\ConnectionManager;

final class CoreSettingsRepository
{
    private ?PDO $pdo = null;
    private string $table;

    public function __construct()
    {
        $this->table = (string) config('database.prefixes.core', 'core_') . 'settings';
    }

    public function available(): bool
    {
        return $this->pdo() !== null;
    }

    public function fetchAll(): array
    {
        $pdo = $this->pdo();
        if ($pdo === null) {
            return [];
        }

        try {
            $stmt = $pdo->query('SELECT category, setting_key, setting_value, is_public FROM ' . $this->table);
            $rows = $stmt !== false ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
        } catch (Throwable) {
            return [];
        }

        $out = [];
        foreach ($rows as $row) {
            $group = trim((string) ($row['category'] ?? ''));
            $subKey = trim((string) ($row['setting_key'] ?? ''));
            if ($group === '' || $subKey === '') {
                continue;
            }

            $fullKey = $group . '.' . $subKey;
            $out[$fullKey] = [
                'group' => $group,
                'key' => $subKey,
                'raw' => (string) ($row['setting_value'] ?? ''),
                'is_public' => (bool) ($row['is_public'] ?? false),
            ];
        }

        return $out;
    }

    public function upsert(string $fullKey, string $group, string $key, ?string $rawValue, bool $isPublic = false): bool
    {
        $pdo = $this->pdo();
        if ($pdo === null) {
            return false;
        }

        try {
            $check = $pdo->prepare('SELECT id FROM ' . $this->table . ' WHERE category = :category AND setting_key = :setting_key LIMIT 1');
            $check->execute(['category' => $group, 'setting_key' => $key]);
            $id = $check->fetchColumn();

            if ($id === false) {
                $insert = $pdo->prepare(
                    'INSERT INTO ' . $this->table . ' (category, setting_key, setting_value, is_public, updated_at) VALUES (:category, :setting_key, :setting_value, :is_public, CURRENT_TIMESTAMP)'
                );
                return $insert->execute([
                    'category' => $group,
                    'setting_key' => $key,
                    'setting_value' => $rawValue,
                    'is_public' => $isPublic ? 1 : 0,
                ]);
            }

            $update = $pdo->prepare(
                'UPDATE ' . $this->table . ' SET setting_value = :setting_value, is_public = :is_public, updated_at = CURRENT_TIMESTAMP WHERE id = :id'
            );
            return $update->execute([
                'id' => (int) $id,
                'setting_value' => $rawValue,
                'is_public' => $isPublic ? 1 : 0,
            ]);
        } catch (Throwable $e) {
            Core\logs\Logger::error('Settings upsert echec', [
                'key' => $fullKey,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function delete(string $group, string $key): bool
    {
        $pdo = $this->pdo();
        if ($pdo === null) {
            return false;
        }

        try {
            $stmt = $pdo->prepare('DELETE FROM ' . $this->table . ' WHERE category = :category AND setting_key = :setting_key');
            return $stmt->execute(['category' => $group, 'setting_key' => $key]);
        } catch (Throwable) {
            return false;
        }
    }

    private function pdo(): ?PDO
    {
        if ($this->pdo !== null) {
            return $this->pdo;
        }

        try {
            $this->pdo = (new ConnectionManager())->connection();
            return $this->pdo;
        } catch (Throwable) {
            return null;
        }
    }
}

