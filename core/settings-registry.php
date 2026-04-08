<?php

declare(strict_types=1);

require_once CATMIN_CORE . '/settings-schema.php';

final class CoreSettingsRegistry
{
    /** @var array<string, array<string, mixed>> */
    private array $schema;

    /** @var array<string, array<string, mixed>> */
    private array $moduleSchema = [];

    public function __construct()
    {
        $this->schema = CoreSettingsSchema::defaults();
    }

    public function has(string $key): bool
    {
        return $this->schemaFor($key) !== null;
    }

    public function schemaFor(string $key): ?array
    {
        $key = trim($key);
        if ($key === '') {
            return null;
        }

        if (isset($this->schema[$key])) {
            return $this->schema[$key];
        }

        if (isset($this->moduleSchema[$key])) {
            return $this->moduleSchema[$key];
        }

        if (str_starts_with($key, 'module.')) {
            return [
                'group' => 'modules',
                'type' => 'json',
                'default' => null,
                'autoload' => false,
                'protected' => false,
                'system' => false,
            ];
        }

        return null;
    }

    public function registerModuleSchema(string $moduleSlug, array $definitions): void
    {
        $moduleSlug = strtolower(trim($moduleSlug));
        if ($moduleSlug === '') {
            return;
        }

        foreach ($definitions as $rawKey => $meta) {
            $rawKey = trim((string) $rawKey);
            if ($rawKey === '') {
                continue;
            }

            $fullKey = str_starts_with($rawKey, 'module.') ? $rawKey : ('module.' . $moduleSlug . '.' . $rawKey);
            if (!is_array($meta)) {
                $meta = [];
            }

            $this->moduleSchema[$fullKey] = [
                'group' => (string) ($meta['group'] ?? 'modules'),
                'type' => (string) ($meta['type'] ?? 'string'),
                'default' => $meta['default'] ?? null,
                'autoload' => (bool) ($meta['autoload'] ?? false),
                'protected' => (bool) ($meta['protected'] ?? false),
                'system' => (bool) ($meta['system'] ?? false),
                'enum' => isset($meta['enum']) && is_array($meta['enum']) ? array_values($meta['enum']) : null,
            ];
        }
    }

    public function defaultsFlat(): array
    {
        $out = [];
        foreach ($this->schema as $key => $meta) {
            $out[$key] = $meta['default'] ?? null;
        }
        foreach ($this->moduleSchema as $key => $meta) {
            $out[$key] = $meta['default'] ?? null;
        }

        return $out;
    }

    public function groups(): array
    {
        return CoreSettingsSchema::groups();
    }
}

