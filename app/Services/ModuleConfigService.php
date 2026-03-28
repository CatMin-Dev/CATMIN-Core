<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;

class ModuleConfigService
{
    /**
     * @return array<string, mixed>
     */
    public static function definition(string $slug): array
    {
        $module = ModuleManager::find($slug);

        if ($module === null) {
            return [];
        }

        $configPath = base_path('modules/' . $module->directory . '/config.php');

        if (!is_file($configPath)) {
            return [];
        }

        $definition = require $configPath;

        return is_array($definition) ? $definition : [];
    }

    public static function hasConfig(string $slug): bool
    {
        return self::fields($slug) !== [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function fields(string $slug): array
    {
        $fields = self::definition($slug)['fields'] ?? [];

        return is_array($fields) ? array_values(array_filter($fields, 'is_array')) : [];
    }

    /**
     * @return array<string, mixed>
     */
    public static function values(string $slug): array
    {
        $values = [];

        foreach (self::fields($slug) as $field) {
            $key = (string) ($field['key'] ?? '');
            if ($key === '') {
                continue;
            }

            $values[$key] = SettingService::get(self::settingKey($slug, $key), $field['default'] ?? null);
        }

        return $values;
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public static function validate(string $slug, array $input): array
    {
        $rules = [];

        foreach (self::fields($slug) as $field) {
            $key = (string) ($field['key'] ?? '');
            if ($key === '') {
                continue;
            }

            $rules[$key] = $field['rules'] ?? 'nullable';
        }

        return Validator::make($input, $rules)->validate();
    }

    /**
     * @param array<string, mixed> $input
     */
    public static function save(string $slug, array $input): void
    {
        foreach (self::fields($slug) as $field) {
            $key = (string) ($field['key'] ?? '');
            if ($key === '') {
                continue;
            }

            $type = (string) ($field['type'] ?? 'string');
            $value = self::normalizeValue($type, $input[$key] ?? ($type === 'boolean' ? false : null));

            SettingService::put(
                self::settingKey($slug, $key),
                $value,
                $type,
                'module.' . $slug,
                (string) ($field['label'] ?? $key),
                false
            );
        }
    }

    public static function get(string $slug, string $key, mixed $default = null): mixed
    {
        return SettingService::get(self::settingKey($slug, $key), $default);
    }

    public static function settingKey(string $slug, string $key): string
    {
        return 'module.' . $slug . '.config.' . $key;
    }

    private static function normalizeValue(string $type, mixed $value): mixed
    {
        return match ($type) {
            'integer' => (int) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOL),
            default => is_scalar($value) || $value === null ? $value : json_encode($value),
        };
    }
}
