<?php

declare(strict_types=1);

final class CoreModuleSnapshotStorage
{
    public function baseDir(): string
    {
        return CATMIN_STORAGE . '/modules/snapshots';
    }

    public function ensure(): void
    {
        foreach ([
            CATMIN_STORAGE . '/modules',
            CATMIN_STORAGE . '/modules/snapshots',
            CATMIN_STORAGE . '/modules/snapshots/index',
            CATMIN_STORAGE . '/modules/snapshots/files',
        ] as $dir) {
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
        }
    }

    public function metadataPath(string $slug, string $snapshotId): string
    {
        return $this->baseDir() . '/index/' . $slug . '--' . $snapshotId . '.json';
    }

    public function filesPath(string $slug, string $snapshotId): string
    {
        return $this->baseDir() . '/files/' . $slug . '--' . $snapshotId;
    }

    /** @return array<int,array<string,mixed>> */
    public function listBySlug(string $slug): array
    {
        $this->ensure();
        $slug = strtolower(trim($slug));
        if ($slug === '') {
            return [];
        }
        $rows = [];
        foreach (glob($this->baseDir() . '/index/' . $slug . '--*.json') ?: [] as $path) {
            $raw = @file_get_contents($path);
            $decoded = is_string($raw) ? json_decode($raw, true) : null;
            if (!is_array($decoded)) {
                continue;
            }
            $rows[] = $decoded;
        }
        usort($rows, static fn (array $a, array $b): int => strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? '')));
        return $rows;
    }
}

