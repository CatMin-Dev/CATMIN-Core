<?php

namespace Addons\CatminBackupS3\Services;

use App\Services\SettingService;
use Illuminate\Support\Facades\Crypt;

class RemoteBackupSettingsService
{
    private const KEY_PREFIX = 'addon.catmin_backup_s3.';

    /** @var array<int, string> */
    private const SECRET_FIELDS = [
        'secret_key',
        'password',
        'private_key',
        'google_service_account_json',
    ];

    /** @return array<string, mixed> */
    public function all(bool $withSecrets = false): array
    {
        $keys = [
            'enabled',
            'provider',
            'prefix',
            'retention_max',
            'endpoint',
            'region',
            'bucket',
            'access_key',
            'secret_key',
            'use_path_style_endpoint',
            'host',
            'port',
            'username',
            'password',
            'root',
            'timeout',
            'passive',
            'ssl',
            'private_key',
            'google_project_id',
            'google_bucket',
            'google_service_account_json',
        ];

        $out = [];
        foreach ($keys as $key) {
            $out[$key] = SettingService::get(self::KEY_PREFIX . $key, $this->defaultFor($key));
        }

        if (!$withSecrets) {
            foreach (self::SECRET_FIELDS as $secretField) {
                if (!empty($out[$secretField])) {
                    $out[$secretField . '_masked'] = '********';
                }
                $out[$secretField] = '';
            }

            return $out;
        }

        foreach (self::SECRET_FIELDS as $secretField) {
            $out[$secretField] = $this->decryptIfNeeded((string) ($out[$secretField] ?? ''));
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function save(array $payload): void
    {
        foreach ($payload as $key => $value) {
            if (!str_starts_with((string) $key, self::KEY_PREFIX)) {
                $fullKey = self::KEY_PREFIX . $key;
            } else {
                $fullKey = (string) $key;
                $key = substr($fullKey, strlen(self::KEY_PREFIX));
            }

            if (in_array((string) $key, self::SECRET_FIELDS, true)) {
                $incoming = trim((string) $value);

                // Keep the current encrypted secret if no new value is provided.
                if ($incoming === '') {
                    continue;
                }

                SettingService::put($fullKey, $this->encryptIfNeeded($incoming), 'string', false, 'catmin-backup-s3');
                continue;
            }

            SettingService::put($fullKey, $value, $this->typeFor($key), false, 'catmin-backup-s3');
        }
    }

    private function defaultFor(string $key): mixed
    {
        return match ($key) {
            'enabled' => false,
            'provider' => 's3',
            'prefix' => 'catmin/backups',
            'retention_max' => 15,
            'endpoint' => '',
            'region' => 'eu-west-1',
            'bucket' => '',
            'access_key' => '',
            'secret_key' => '',
            'use_path_style_endpoint' => true,
            'host' => '',
            'port' => 22,
            'username' => '',
            'password' => '',
            'root' => '/backups/catmin',
            'timeout' => 30,
            'passive' => true,
            'ssl' => false,
            'private_key' => '',
            'google_project_id' => '',
            'google_bucket' => '',
            'google_service_account_json' => '',
            default => null,
        };
    }

    private function typeFor(string $key): string
    {
        return match ($key) {
            'enabled', 'use_path_style_endpoint', 'passive', 'ssl' => 'boolean',
            'retention_max', 'port', 'timeout' => 'integer',
            default => 'string',
        };
    }

    private function encryptIfNeeded(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if (str_starts_with($value, 'enc::')) {
            return $value;
        }

        return 'enc::' . Crypt::encryptString($value);
    }

    private function decryptIfNeeded(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if (!str_starts_with($value, 'enc::')) {
            return $value;
        }

        try {
            return Crypt::decryptString(substr($value, 5));
        } catch (\Throwable) {
            return '';
        }
    }
}
