<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;

class SettingsTransferService
{
    /**
     * @return array{meta: array<string, mixed>, settings: array<int, array<string, mixed>>}
     */
    public static function exportPayload(bool $includeDefaults = false): array
    {
        $settings = Setting::query()
            ->orderBy('key')
            ->get(['key', 'value', 'type', 'group', 'description', 'is_public'])
            ->map(fn (Setting $setting) => [
                'key' => (string) $setting->key,
                'value' => $setting->value,
                'type' => (string) ($setting->type ?? 'string'),
                'group' => $setting->group,
                'description' => $setting->description,
                'is_public' => (bool) $setting->is_public,
                'source' => 'database',
            ])
            ->values()
            ->all();

        if ($includeDefaults) {
            $defaults = collect((array) config('catmin.settings.defaults', []))
                ->map(fn ($value, $key) => [
                    'key' => (string) $key,
                    'value' => is_scalar($value) || $value === null ? $value : json_encode($value),
                    'type' => gettype($value),
                    'group' => self::groupFromKey((string) $key),
                    'description' => null,
                    'is_public' => false,
                    'source' => 'default',
                ])
                ->values()
                ->all();

            $settings = collect($settings)
                ->merge($defaults)
                ->unique('key')
                ->values()
                ->all();
        }

        return [
            'meta' => [
                'format' => 'catmin.settings.export.v1',
                'generated_at' => now()->toIso8601String(),
                'app_url' => (string) config('app.url', ''),
                'include_defaults' => $includeDefaults,
            ],
            'settings' => $settings,
        ];
    }

    public static function exportToFile(string $path, bool $includeDefaults = false): void
    {
        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode(self::exportPayload($includeDefaults), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * @return array{created: int, updated: int, skipped: int, errors: array<int, string>, warnings: array<int, string>}
     */
    public static function importFromFile(string $path, bool $overwrite = false, bool $dryRun = false, bool $allowProtected = false): array
    {
        if (!File::exists($path)) {
            throw new InvalidArgumentException('Fichier import introuvable: ' . $path);
        }

        $raw = File::get($path);
        $payload = json_decode($raw, true);

        if (!is_array($payload)) {
            throw new InvalidArgumentException('JSON invalide (objet attendu).');
        }

        $format = (string) data_get($payload, 'meta.format', '');
        if ($format !== 'catmin.settings.export.v1') {
            throw new InvalidArgumentException('Format import non reconnu.');
        }

        $rows = data_get($payload, 'settings', []);
        if (!is_array($rows)) {
            throw new InvalidArgumentException('Cle settings invalide (tableau attendu).');
        }

        $result = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
            'warnings' => [],
        ];

        foreach ($rows as $index => $row) {
            if (!is_array($row)) {
                $result['errors'][] = "Entree #{$index} invalide (objet attendu).";
                continue;
            }

            $key = trim((string) ($row['key'] ?? ''));
            if ($key === '') {
                $result['errors'][] = "Entree #{$index} sans key.";
                continue;
            }

            if (self::isProtectedKey($key) && !$allowProtected) {
                $result['warnings'][] = "Cle protegee ignoree: {$key}";
                $result['skipped']++;
                continue;
            }

            $exists = Setting::query()->where('key', $key)->exists();
            if ($exists && !$overwrite) {
                $result['skipped']++;
                continue;
            }

            $data = [
                'value' => self::normalizeValue($row['value'] ?? null),
                'type' => (string) ($row['type'] ?? 'string'),
                'group' => self::nullableString($row['group'] ?? null),
                'description' => self::nullableString($row['description'] ?? null),
                'is_public' => (bool) ($row['is_public'] ?? false),
            ];

            if ($dryRun) {
                $result[$exists ? 'updated' : 'created']++;
                continue;
            }

            if ($exists) {
                Setting::query()->where('key', $key)->update($data);
                $result['updated']++;
            } else {
                Setting::query()->create(array_merge(['key' => $key], $data));
                $result['created']++;
            }
        }

        if (!$dryRun) {
            SettingService::forgetCache();
        }

        return $result;
    }

    protected static function isProtectedKey(string $key): bool
    {
        $blockedExact = [
            'app.key',
            'catmin.admin.password',
            'catmin.admin.username',
        ];

        $blockedPrefixes = [
            'database.',
            'mail.mailers.',
            'queue.connections.',
        ];

        if (in_array($key, $blockedExact, true)) {
            return true;
        }

        foreach ($blockedPrefixes as $prefix) {
            if (str_starts_with($key, $prefix)) {
                return true;
            }
        }

        return false;
    }

    protected static function normalizeValue(mixed $value): mixed
    {
        if (is_scalar($value) || $value === null) {
            return $value;
        }

        return json_encode($value);
    }

    protected static function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    protected static function groupFromKey(string $key): ?string
    {
        if (!str_contains($key, '.')) {
            return null;
        }

        return explode('.', $key)[0] ?: null;
    }
}
