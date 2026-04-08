<?php

declare(strict_types=1);

use Core\database\ConnectionManager;

final class CoreAppsRepository
{
    public function listEnabled(): array
    {
        try {
            $pdo = (new ConnectionManager())->connection();
            $stmt = $pdo->query('SELECT id, label, icon, url, type, target, is_enabled, sort_order FROM ' . $this->table() . ' WHERE is_enabled = 1 ORDER BY sort_order ASC, id ASC');
            $rows = $stmt !== false ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
            return is_array($rows) ? $rows : [];
        } catch (\Throwable) {
            return [];
        }
    }

    public function listAll(): array
    {
        try {
            $pdo = (new ConnectionManager())->connection();
            $stmt = $pdo->query('SELECT id, label, icon, url, type, target, is_enabled, sort_order, created_at, updated_at FROM ' . $this->table() . ' ORDER BY sort_order ASC, id ASC');
            $rows = $stmt !== false ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
            return is_array($rows) ? $rows : [];
        } catch (\Throwable) {
            return [];
        }
    }

    public function find(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        try {
            $pdo = (new ConnectionManager())->connection();
            $stmt = $pdo->prepare('SELECT id, label, icon, url, type, target, is_enabled, sort_order FROM ' . $this->table() . ' WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return is_array($row) ? $row : null;
        } catch (\Throwable) {
            return null;
        }
    }

    public function create(array $data): bool
    {
        try {
            $pdo = (new ConnectionManager())->connection();
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
        } catch (\Throwable) {
            return false;
        }
    }

    public function update(int $id, array $data): bool
    {
        if ($id <= 0) {
            return false;
        }

        try {
            $pdo = (new ConnectionManager())->connection();
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
        } catch (\Throwable) {
            return false;
        }
    }

    public function delete(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        try {
            $pdo = (new ConnectionManager())->connection();
            $stmt = $pdo->prepare('DELETE FROM ' . $this->table() . ' WHERE id = :id');
            return $stmt->execute(['id' => $id]);
        } catch (\Throwable) {
            return false;
        }
    }

    private function table(): string
    {
        return (string) config('database.prefixes.core', 'core_') . 'apps';
    }
}
