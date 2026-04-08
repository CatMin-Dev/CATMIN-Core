<?php

declare(strict_types=1);

use Core\database\ConnectionManager;

final class CoreModuleStateStore
{
    public function stateBySlug(): array
    {
        try {
            $pdo = (new ConnectionManager())->connection();
            $table = (string) config('database.prefixes.core', 'core_') . 'modules';
            $stmt = $pdo->query('SELECT slug, status, version, updated_at, installed_at FROM ' . $table);
            $rows = $stmt !== false ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
            $state = [];
            foreach ($rows as $row) {
                $slug = strtolower(trim((string) ($row['slug'] ?? '')));
                if ($slug === '') {
                    continue;
                }
                $state[$slug] = $row;
            }
            return $state;
        } catch (Throwable) {
            return [];
        }
    }

    public function persist(string $slug, string $name, string $version, bool $enabled): void
    {
        try {
            $pdo = (new ConnectionManager())->connection();
            $table = (string) config('database.prefixes.core', 'core_') . 'modules';

            $check = $pdo->prepare('SELECT id FROM ' . $table . ' WHERE slug = :slug LIMIT 1');
            $check->execute(['slug' => $slug]);
            $id = $check->fetchColumn();

            if ($id === false) {
                $insert = $pdo->prepare(
                    'INSERT INTO ' . $table . ' (name, slug, version, status, installed_at, updated_at) VALUES (:name, :slug, :version, :status, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
                );
                $insert->execute([
                    'name' => $name,
                    'slug' => $slug,
                    'version' => $version,
                    'status' => $enabled ? 'active' : 'inactive',
                ]);
                return;
            }

            $update = $pdo->prepare('UPDATE ' . $table . ' SET name = :name, version = :version, status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
            $update->execute([
                'id' => (int) $id,
                'name' => $name,
                'version' => $version,
                'status' => $enabled ? 'active' : 'inactive',
            ]);
        } catch (Throwable) {
        }
    }
}

