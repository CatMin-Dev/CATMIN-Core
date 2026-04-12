<?php

declare(strict_types=1);

require_once CATMIN_CORE . '/settings-registry.php';
require_once CATMIN_CORE . '/settings-validator.php';
require_once CATMIN_CORE . '/settings-cache.php';
require_once CATMIN_CORE . '/settings-repository.php';

final class CoreSettingsEngine
{
    private CoreSettingsRegistry $registry;
    private CoreSettingsValidator $validator;
    private CoreSettingsCache $cache;
    private CoreSettingsRepository $repository;

    /** @var array<string, mixed> */
    private array $data = [];

    /** @var array<string, mixed> */
    private array $dirty = [];

    private bool $loaded = false;

    public function __construct(
        ?CoreSettingsRegistry $registry = null,
        ?CoreSettingsValidator $validator = null,
        ?CoreSettingsCache $cache = null,
        ?CoreSettingsRepository $repository = null
    ) {
        $this->registry = $registry ?? new CoreSettingsRegistry();
        $this->validator = $validator ?? new CoreSettingsValidator();
        $this->cache = $cache ?? new CoreSettingsCache();
        $this->repository = $repository ?? new CoreSettingsRepository();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->loadIfNeeded();
        return array_key_exists($key, $this->data) ? $this->data[$key] : $default;
    }

    public function has(string $key): bool
    {
        $this->loadIfNeeded();
        return array_key_exists($key, $this->data);
    }

    public function set(string $key, mixed $value, bool $privileged = true): bool
    {
        $this->loadIfNeeded();
        $schema = $this->registry->schemaFor($key);
        if ($schema === null) {
            Core\logs\Logger::error('Setting refusee (cle inconnue)', ['key' => $key]);
            return false;
        }

        if ((bool) ($schema['protected'] ?? false) && !$privileged) {
            Core\logs\Logger::error('Setting refusee (protegee)', ['key' => $key]);
            return false;
        }

        $validation = $this->validator->validate($key, $value, $schema);
        if (!((bool) ($validation['valid'] ?? false))) {
            Core\logs\Logger::error('Validation setting refusee', [
                'key' => $key,
                'reason' => (string) ($validation['error'] ?? 'unknown'),
            ]);
            return false;
        }

        $normalized = $validation['value'] ?? null;
        $this->data[$key] = $normalized;
        $this->dirty[$key] = $normalized;
        return true;
    }

    public function forget(string $key, bool $privileged = true): bool
    {
        $this->loadIfNeeded();
        $schema = $this->registry->schemaFor($key);
        if ($schema === null) {
            return false;
        }
        if ((bool) ($schema['protected'] ?? false) && !$privileged) {
            return false;
        }

        [$group, $subKey] = $this->splitKey($key);
        if ($group === '' || $subKey === '') {
            return false;
        }

        $deleted = $this->repository->delete($group, $subKey);
        if (!$deleted) {
            return false;
        }

        unset($this->data[$key], $this->dirty[$key]);
        $this->cache->save($this->data);
        Core\logs\Logger::info('Setting supprimee', ['key' => $key]);
        return true;
    }

    public function all(?string $group = null): array
    {
        $this->loadIfNeeded();

        if ($group === null || trim($group) === '') {
            return $this->grouped($this->data);
        }

        $group = strtolower(trim($group));
        $out = [];
        foreach ($this->data as $key => $value) {
            if (!str_starts_with($key, $group . '.')) {
                continue;
            }
            $out[substr($key, strlen($group) + 1)] = $value;
        }

        return $out;
    }

    public function validate(string $key, mixed $value): array
    {
        $schema = $this->registry->schemaFor($key);
        if ($schema === null) {
            return ['valid' => false, 'value' => null, 'error' => 'Cle inconnue'];
        }
        return $this->validator->validate($key, $value, $schema);
    }

    public function save(): bool
    {
        $this->loadIfNeeded();
        if ($this->dirty === []) {
            return true;
        }

        if (!$this->repository->available()) {
            Core\logs\Logger::error('DB indisponible: save settings annule');
            return false;
        }

        $ok = true;
        foreach ($this->dirty as $key => $value) {
            $schema = $this->registry->schemaFor($key);
            if ($schema === null) {
                $ok = false;
                continue;
            }

            [$group, $subKey] = $this->splitKey($key);
            if ($group === '' || $subKey === '') {
                $ok = false;
                continue;
            }

            $raw = $this->toStorage($schema, $value);
            $saved = $this->repository->upsert(
                $key,
                $group,
                $subKey,
                $raw,
                (bool) ($schema['is_public'] ?? false)
            );
            if (!$saved) {
                $ok = false;
                continue;
            }

            Core\logs\Logger::info('Setting mise a jour', [
                'key' => $key,
                'value' => $this->maskForLog($key, $value),
            ]);
        }

        if ($ok) {
            $this->dirty = [];
            $this->cache->save($this->data);
        }

        return $ok;
    }

    public function flushCache(): bool
    {
        $ok = $this->cache->flush();
        if ($ok) {
            Core\logs\Logger::info('Settings cache flush');
        }
        return $ok;
    }

    private function loadIfNeeded(): void
    {
        if ($this->loaded) {
            return;
        }

        $defaults = $this->registry->defaultsFlat();
        if ($this->repository->available()) {
            $rows = $this->repository->fetchAll();
            $data = $defaults;
            foreach ($rows as $fullKey => $row) {
                $schema = $this->registry->schemaFor($fullKey);
                if ($schema === null) {
                    continue;
                }

                $typed = $this->fromStorage($schema, (string) ($row['raw'] ?? ''));
                $data[$fullKey] = $typed;
            }

            $this->data = $data;
            $this->loaded = true;
            $this->cache->save($this->data);
            return;
        }

        $cacheData = $this->cache->load();
        if (is_array($cacheData) && $cacheData !== []) {
            $legacyMap = [
                'backup.local.enabled' => 'backup.local_enabled',
                'legal.bundle.version' => 'legal.bundle_version',
            ];
            $normalized = [];
            foreach ($cacheData as $key => $value) {
                $rawKey = trim((string) $key);
                if ($rawKey === '') {
                    continue;
                }
                $finalKey = $legacyMap[$rawKey] ?? $rawKey;
                if ($this->registry->schemaFor($finalKey) === null) {
                    continue;
                }
                $normalized[$finalKey] = $value;
            }

            $this->data = array_merge($defaults, $normalized);
            $this->loaded = true;
            return;
        }

        $this->data = $defaults;
        $this->loaded = true;
        Core\logs\Logger::error('Settings fallback defaults (DB indisponible)');
    }

    private function grouped(array $flat): array
    {
        $out = [];
        foreach ($flat as $key => $value) {
            [$group, $sub] = $this->splitKey($key);
            if ($group === '' || $sub === '') {
                continue;
            }
            if (!isset($out[$group])) {
                $out[$group] = [];
            }
            $out[$group][$sub] = $value;
        }
        return $out;
    }

    private function splitKey(string $key): array
    {
        $parts = explode('.', $key, 2);
        if (count($parts) !== 2) {
            return ['', ''];
        }
        return [trim($parts[0]), trim($parts[1])];
    }

    private function fromStorage(array $schema, string $raw): mixed
    {
        $type = strtolower((string) ($schema['type'] ?? 'string'));
        return match ($type) {
            'bool' => in_array(strtolower($raw), ['1', 'true', 'yes', 'on'], true),
            'int' => (int) $raw,
            'json', 'array' => $raw === '' ? null : (json_decode($raw, true) ?? null),
            default => $raw,
        };
    }

    private function toStorage(array $schema, mixed $value): ?string
    {
        $type = strtolower((string) ($schema['type'] ?? 'string'));
        return match ($type) {
            'bool' => $value ? '1' : '0',
            'int' => (string) ((int) $value),
            'json', 'array' => $value === null ? null : (json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: null),
            default => trim((string) $value),
        };
    }

    private function maskForLog(string $key, mixed $value): mixed
    {
        $k = strtolower($key);
        foreach (['password', 'secret', 'token'] as $needle) {
            if (str_contains($k, $needle)) {
                return '***';
            }
        }
        return is_scalar($value) || $value === null ? $value : '[complex]';
    }
}
