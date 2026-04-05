<?php

declare(strict_types=1);

namespace Core\config;

use RuntimeException;

final class RuntimeConfigLoader
{
    private const ALLOWED_RUNTIME_KEYS = [
        'security.admin_path',
        'app.name',
        'app.url',
        'database.default',
    ];

    public function __construct(
        private readonly ConfigRepository $repository,
        private readonly EnvManager $env
    ) {}

    public function load(string $configDirectory, string $runtimeConfigPath): void
    {
        $this->repository->loadDirectory($configDirectory);
        $this->applyEnvOverrides();
        $this->applyRuntimeOverrides($runtimeConfigPath);
    }

    public function writeRuntimeConfig(string $runtimeConfigPath, array $updates): void
    {
        $safe = $this->sanitizeRuntimeUpdates($updates);

        $existing = [];
        if (is_file($runtimeConfigPath)) {
            $raw = file_get_contents($runtimeConfigPath);
            $decoded = is_string($raw) ? json_decode($raw, true) : null;
            if (is_array($decoded)) {
                $existing = $decoded;
            }
        }

        $merged = array_replace_recursive($existing, $safe);

        $dir = dirname($runtimeConfigPath);
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('Unable to create runtime config directory.');
        }

        $json = json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($json)) {
            throw new RuntimeException('Unable to encode runtime config payload.');
        }

        $tmpFile = $runtimeConfigPath . '.tmp';
        file_put_contents($tmpFile, $json, LOCK_EX);
        rename($tmpFile, $runtimeConfigPath);
    }

    private function applyEnvOverrides(): void
    {
        $mapping = [
            'CATMIN_ADMIN_PATH' => 'security.admin_path',
            'APP_NAME' => 'app.name',
            'APP_URL' => 'app.url',
            'CATMIN_DB_DRIVER' => 'database.default',
        ];

        foreach ($mapping as $envKey => $configKey) {
            $value = $this->env->get($envKey);
            if (!is_string($value) || $value === '') {
                continue;
            }

            if ($configKey === 'security.admin_path') {
                $value = $this->sanitizeAdminPath($value);
            }

            $this->repository->setByPath($configKey, $value);
        }
    }

    private function applyRuntimeOverrides(string $runtimeConfigPath): void
    {
        if (!is_file($runtimeConfigPath)) {
            return;
        }

        $raw = file_get_contents($runtimeConfigPath);
        if (!is_string($raw) || trim($raw) === '') {
            return;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return;
        }

        foreach ($this->flatten($decoded) as $key => $value) {
            if (!in_array($key, self::ALLOWED_RUNTIME_KEYS, true)) {
                continue;
            }

            if ($key === 'security.admin_path' && is_string($value)) {
                $value = $this->sanitizeAdminPath($value);
            }

            $this->repository->setByPath($key, $value);
        }
    }

    private function sanitizeRuntimeUpdates(array $updates): array
    {
        $safe = [];

        foreach ($this->flatten($updates) as $key => $value) {
            if (!in_array($key, self::ALLOWED_RUNTIME_KEYS, true)) {
                continue;
            }

            if ($key === 'security.admin_path' && is_string($value)) {
                $value = $this->sanitizeAdminPath($value);
            }

            $this->setNested($safe, $key, $value);
        }

        return $safe;
    }

    private function sanitizeAdminPath(string $value): string
    {
        $trimmed = trim($value);
        $trimmed = trim($trimmed, '/');
        $trimmed = preg_replace('/[^a-zA-Z0-9\-\_\/]/', '', $trimmed) ?? 'admin';

        return $trimmed !== '' ? $trimmed : 'admin';
    }

    /** @return array<string, mixed> */
    private function flatten(array $source, string $prefix = ''): array
    {
        $flattened = [];

        foreach ($source as $key => $value) {
            $full = $prefix === '' ? (string) $key : $prefix . '.' . (string) $key;

            if (is_array($value) && $value !== [] && array_is_list($value) === false) {
                $flattened += $this->flatten($value, $full);
                continue;
            }

            $flattened[$full] = $value;
        }

        return $flattened;
    }

    private function setNested(array &$target, string $key, mixed $value): void
    {
        $segments = explode('.', $key);
        $cursor = &$target;

        foreach ($segments as $segment) {
            if (!isset($cursor[$segment]) || !is_array($cursor[$segment])) {
                $cursor[$segment] = [];
            }
            $cursor = &$cursor[$segment];
        }

        $cursor = $value;
    }
}
