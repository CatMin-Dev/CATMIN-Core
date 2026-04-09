<?php

declare(strict_types=1);

use Core\database\ConnectionManager;

final class CoreQueueEngine
{
    private string $table;

    public function __construct()
    {
        $this->table = (string) config('database.prefixes.core', 'core_') . 'queue_jobs';
    }

    public function enqueue(string $jobType, array $payload = [], string $queue = 'default', int $delaySeconds = 0, int $maxAttempts = 3): array
    {
        $jobType = trim($jobType);
        if ($jobType === '') {
            return ['ok' => false, 'message' => 'job_type requis', 'id' => 0];
        }

        try {
            $pdo = (new ConnectionManager())->connection();
            $stmt = $pdo->prepare(
                'INSERT INTO ' . $this->table . ' (queue, job_type, payload, status, attempts, max_attempts, available_at, created_at, updated_at)
                 VALUES (:queue, :job_type, :payload, :status, 0, :max_attempts, :available_at, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
            );
            $availableAt = gmdate('Y-m-d H:i:s', time() + max(0, $delaySeconds));
            $ok = $stmt->execute([
                'queue' => mb_substr($queue, 0, 60),
                'job_type' => mb_substr($jobType, 0, 140),
                'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}',
                'status' => 'pending',
                'max_attempts' => max(1, $maxAttempts),
                'available_at' => $availableAt,
            ]);
            $id = $ok ? (int) $pdo->lastInsertId() : 0;
            return ['ok' => $ok, 'message' => $ok ? 'Job enqueued' : 'Job enqueue failed', 'id' => $id];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Queue error: ' . $e->getMessage(), 'id' => 0];
        }
    }

    public function reserve(string $queue = 'default'): ?array
    {
        try {
            $pdo = (new ConnectionManager())->connection();
            $sql = 'SELECT id, queue, job_type, payload, attempts, max_attempts
                    FROM ' . $this->table . '
                    WHERE queue = :queue
                      AND status IN (\'pending\', \'retry\')
                      AND (available_at IS NULL OR available_at <= CURRENT_TIMESTAMP)
                    ORDER BY id ASC
                    LIMIT 1';
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['queue' => $queue]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!is_array($row)) {
                return null;
            }

            $attempts = (int) ($row['attempts'] ?? 0) + 1;
            $up = $pdo->prepare(
                'UPDATE ' . $this->table . ' SET status = :status, attempts = :attempts, reserved_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP WHERE id = :id'
            );
            $up->execute([
                'status' => 'running',
                'attempts' => $attempts,
                'id' => (int) ($row['id'] ?? 0),
            ]);

            return [
                'id' => (int) ($row['id'] ?? 0),
                'queue' => (string) ($row['queue'] ?? 'default'),
                'job_type' => (string) ($row['job_type'] ?? ''),
                'payload' => json_decode((string) ($row['payload'] ?? '{}'), true) ?: [],
                'attempts' => $attempts,
                'max_attempts' => (int) ($row['max_attempts'] ?? 1),
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    public function complete(int $jobId): bool
    {
        return $this->setFinalStatus($jobId, 'done', null);
    }

    public function failOrRetry(int $jobId, string $errorMessage, int $delaySeconds = 60): bool
    {
        try {
            $pdo = (new ConnectionManager())->connection();
            $stmt = $pdo->prepare('SELECT attempts, max_attempts FROM ' . $this->table . ' WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $jobId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!is_array($row)) {
                return false;
            }
            $attempts = (int) ($row['attempts'] ?? 0);
            $max = max(1, (int) ($row['max_attempts'] ?? 1));
            if ($attempts >= $max) {
                return $this->setFinalStatus($jobId, 'failed', $errorMessage);
            }

            $availableAt = gmdate('Y-m-d H:i:s', time() + max(1, $delaySeconds));
            $up = $pdo->prepare(
                'UPDATE ' . $this->table . ' SET status = :status, available_at = :available_at, last_error = :last_error, updated_at = CURRENT_TIMESTAMP WHERE id = :id'
            );
            return $up->execute([
                'status' => 'retry',
                'available_at' => $availableAt,
                'last_error' => mb_substr($errorMessage, 0, 4000),
                'id' => $jobId,
            ]);
        } catch (\Throwable) {
            return false;
        }
    }

    public function listRecent(string $queue = 'default', int $limit = 100): array
    {
        try {
            $pdo = (new ConnectionManager())->connection();
            $stmt = $pdo->prepare(
                'SELECT id, queue, job_type, status, attempts, max_attempts, available_at, reserved_at, finished_at, last_error, created_at
                 FROM ' . $this->table . '
                 WHERE queue = :queue
                 ORDER BY id DESC
                 LIMIT :limit'
            );
            $stmt->bindValue(':queue', $queue, PDO::PARAM_STR);
            $stmt->bindValue(':limit', max(1, min(500, $limit)), PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return is_array($rows) ? $rows : [];
        } catch (\Throwable) {
            return [];
        }
    }

    public function stats(string $queue = 'default'): array
    {
        $stats = ['pending' => 0, 'running' => 0, 'retry' => 0, 'failed' => 0, 'done' => 0];
        try {
            $pdo = (new ConnectionManager())->connection();
            $stmt = $pdo->prepare('SELECT status, COUNT(*) AS c FROM ' . $this->table . ' WHERE queue = :queue GROUP BY status');
            $stmt->execute(['queue' => $queue]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach (is_array($rows) ? $rows : [] as $row) {
                $status = strtolower((string) ($row['status'] ?? ''));
                if (array_key_exists($status, $stats)) {
                    $stats[$status] = (int) ($row['c'] ?? 0);
                }
            }
        } catch (\Throwable) {
        }
        return $stats;
    }

    private function setFinalStatus(int $jobId, string $status, ?string $errorMessage): bool
    {
        try {
            $pdo = (new ConnectionManager())->connection();
            $up = $pdo->prepare(
                'UPDATE ' . $this->table . ' SET status = :status, finished_at = CURRENT_TIMESTAMP, last_error = :last_error, updated_at = CURRENT_TIMESTAMP WHERE id = :id'
            );
            return $up->execute([
                'status' => $status,
                'last_error' => $errorMessage !== null ? mb_substr($errorMessage, 0, 4000) : null,
                'id' => $jobId,
            ]);
        } catch (\Throwable) {
            return false;
        }
    }
}

