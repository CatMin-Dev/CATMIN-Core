<?php

declare(strict_types=1);

use Core\database\ConnectionManager;

final class CoreNotificationsRepository
{
    public function listRecent(int $limit = 8): array
    {
        $limit = max(1, min(50, $limit));
        try {
            $pdo = (new ConnectionManager())->connection();
            $table = $this->table();
            $stmt = $pdo->prepare(
                'SELECT id, title, message, type, source, action_url, is_read, created_by, created_at FROM ' . $table . ' ORDER BY id DESC LIMIT :limit'
            );
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return is_array($rows) ? $rows : [];
        } catch (\Throwable) {
            return [];
        }
    }

    public function listAll(int $limit = 200): array
    {
        $limit = max(10, min(1000, $limit));
        try {
            $pdo = (new ConnectionManager())->connection();
            $table = $this->table();
            $stmt = $pdo->prepare(
                'SELECT id, title, message, type, source, action_url, is_read, created_by, created_at FROM ' . $table . ' ORDER BY id DESC LIMIT :limit'
            );
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return is_array($rows) ? $rows : [];
        } catch (\Throwable) {
            return [];
        }
    }

    public function countUnread(): int
    {
        try {
            $pdo = (new ConnectionManager())->connection();
            $stmt = $pdo->query('SELECT COUNT(*) FROM ' . $this->table() . ' WHERE is_read = 0');
            return (int) (($stmt !== false ? $stmt->fetchColumn() : 0) ?: 0);
        } catch (\Throwable) {
            return 0;
        }
    }

    public function markRead(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }
        try {
            $pdo = (new ConnectionManager())->connection();
            $stmt = $pdo->prepare('UPDATE ' . $this->table() . ' SET is_read = 1 WHERE id = :id');
            return $stmt->execute(['id' => $id]);
        } catch (\Throwable) {
            return false;
        }
    }

    public function markAllRead(): bool
    {
        try {
            $pdo = (new ConnectionManager())->connection();
            return $pdo->exec('UPDATE ' . $this->table() . ' SET is_read = 1 WHERE is_read = 0') !== false;
        } catch (\Throwable) {
            return false;
        }
    }

    public function push(array $payload): bool
    {
        $title = trim((string) ($payload['title'] ?? 'Notification'));
        if ($title === '') {
            return false;
        }

        $type = strtolower(trim((string) ($payload['type'] ?? 'info')));
        if (!in_array($type, ['info', 'success', 'warning', 'danger', 'security', 'system', 'module'], true)) {
            $type = 'info';
        }

        $source = trim((string) ($payload['source'] ?? 'core'));
        if ($source === '') {
            $source = 'core';
        }

        $message = trim((string) ($payload['message'] ?? ''));
        $actionUrl = trim((string) ($payload['action_url'] ?? ''));
        $createdBy = (int) ($payload['created_by'] ?? 0);

        try {
            $pdo = (new ConnectionManager())->connection();
            $stmt = $pdo->prepare(
                'INSERT INTO ' . $this->table() . ' (title, message, type, source, action_url, is_read, created_by, created_at) VALUES (:title, :message, :type, :source, :action_url, 0, :created_by, CURRENT_TIMESTAMP)'
            );
            return $stmt->execute([
                'title' => mb_substr($title, 0, 191),
                'message' => $message !== '' ? $message : null,
                'type' => $type,
                'source' => mb_substr($source, 0, 120),
                'action_url' => $actionUrl !== '' ? mb_substr($actionUrl, 0, 255) : null,
                'created_by' => $createdBy > 0 ? $createdBy : null,
            ]);
        } catch (\Throwable) {
            return false;
        }
    }

    private function table(): string
    {
        return (string) config('database.prefixes.core', 'core_') . 'notification_center';
    }
}

