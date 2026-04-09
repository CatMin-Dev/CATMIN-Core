<?php

declare(strict_types=1);

final class CoreKeyringCache
{
    private string $trustDir;
    private string $keyringCacheFile;
    private string $registryCacheFile;

    public function __construct(?string $trustDir = null)
    {
        $this->trustDir = $trustDir ?: storage_path('trust');
        $this->keyringCacheFile = $this->trustDir . '/keyring-cache.json';
        $this->registryCacheFile = $this->trustDir . '/trust-registry-cache.json';
    }

    public function trustDir(): string
    {
        return $this->trustDir;
    }

    public function ensureStorage(): bool
    {
        if (is_dir($this->trustDir)) {
            return is_writable($this->trustDir);
        }

        return @mkdir($this->trustDir, 0755, true);
    }

    public function keyringCacheFile(): string
    {
        return $this->keyringCacheFile;
    }

    public function registryCacheFile(): string
    {
        return $this->registryCacheFile;
    }

    public function loadKeyringCache(): array
    {
        $default = [
            'updated_at' => null,
            'last_sync_at' => null,
            'last_sync_status' => 'disabled',
            'last_sync_message' => 'registry distant non configure',
            'last_import_at' => null,
            'last_import_status' => 'never',
            'last_import_message' => '',
            'remote_keys' => [],
            'imported_official_keys' => [],
            'local_keys' => [],
            'revocations' => [],
            'revoked' => [],
        ];

        return $this->readJson($this->keyringCacheFile, $default);
    }

    public function saveKeyringCache(array $cache): bool
    {
        $cache['updated_at'] = gmdate('c');
        return $this->writeJson($this->keyringCacheFile, $cache);
    }

    public function loadRegistryCache(): array
    {
        $default = [
            'updated_at' => null,
            'publishers' => [],
            'keys' => [],
            'metadata' => [],
        ];

        return $this->readJson($this->registryCacheFile, $default);
    }

    public function saveRegistryCache(array $cache): bool
    {
        $cache['updated_at'] = gmdate('c');
        return $this->writeJson($this->registryCacheFile, $cache);
    }

    private function readJson(string $file, array $default): array
    {
        if (!is_file($file)) {
            return $default;
        }

        $raw = @file_get_contents($file);
        if (!is_string($raw) || trim($raw) === '') {
            return $default;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return $default;
        }

        return array_replace($default, $decoded);
    }

    private function writeJson(string $file, array $payload): bool
    {
        if (!$this->ensureStorage()) {
            return false;
        }

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($json)) {
            return false;
        }

        return @file_put_contents($file, $json . "\n", LOCK_EX) !== false;
    }
}
