<?php

namespace Addons\CatminBackupS3\Services\StorageProviders;

use League\Flysystem\Filesystem;
use League\Flysystem\StorageAttributes;

abstract class AbstractFlysystemProvider implements RemoteStorageProviderInterface
{
    protected Filesystem $filesystem;

    public function upload(string $localPath, string $remotePath): void
    {
        $stream = fopen($localPath, 'rb');
        if ($stream === false) {
            throw new \RuntimeException('Impossible de lire le fichier local: ' . $localPath);
        }

        try {
            $this->filesystem->writeStream($remotePath, $stream);
        } finally {
            fclose($stream);
        }
    }

    public function list(string $prefix = ''): array
    {
        $items = [];

        /** @var iterable<StorageAttributes> $listing */
        $listing = $this->filesystem->listContents($prefix, true);

        foreach ($listing as $entry) {
            if (!$entry->isFile()) {
                continue;
            }

            $items[] = [
                'path' => $entry->path(),
                'size' => (int) ($entry->fileSize() ?? 0),
                'last_modified' => (int) ($entry->lastModified() ?? 0),
                'type' => pathinfo($entry->path(), PATHINFO_EXTENSION) ?: 'bin',
                'source' => $this->sourceLabel(),
            ];
        }

        usort($items, static fn (array $a, array $b) => ($b['last_modified'] <=> $a['last_modified']));

        return $items;
    }

    public function download(string $remotePath, string $localPath): void
    {
        $dir = dirname($localPath);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new \RuntimeException('Impossible de creer le dossier local: ' . $dir);
        }

        $stream = $this->filesystem->readStream($remotePath);
        if (!is_resource($stream)) {
            throw new \RuntimeException('Impossible de lire le fichier distant: ' . $remotePath);
        }

        $target = fopen($localPath, 'wb');
        if ($target === false) {
            fclose($stream);
            throw new \RuntimeException('Impossible d\'ouvrir le fichier local: ' . $localPath);
        }

        try {
            stream_copy_to_stream($stream, $target);
        } finally {
            fclose($stream);
            fclose($target);
        }
    }

    public function delete(string $remotePath): void
    {
        $this->filesystem->delete($remotePath);
    }

    public function testConnection(): bool
    {
        try {
            $this->filesystem->listContents('', false);
            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
