<?php

namespace Modules\Settings\Services;

use App\Models\Setting;
use App\Services\SettingService;

class SettingsAdminService
{
    /**
     * @return array<string, mixed>
     */
    public function essentials(): array
    {
        return [
            'site_name' => (string) SettingService::get('site.name', config('catmin.settings.defaults.site.name', 'CATMIN')),
            'site_url' => (string) SettingService::get('site.url', config('catmin.settings.defaults.site.url', config('app.url'))),
            'admin_path' => (string) SettingService::get('admin.path', config('catmin.admin.path', 'admin')),
            'admin_theme' => (string) SettingService::get('admin.theme', config('catmin.settings.defaults.admin.theme', 'catmin-light')),
            'site_frontend_enabled' => $this->toBool(SettingService::get('site.frontend_enabled', config('catmin.settings.defaults.site.frontend_enabled', true))),
            'registration_open' => $this->toBool(SettingService::get('site.registration_open', false)),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function updateEssentials(array $payload): void
    {
        $adminPath = trim((string) $payload['admin_path']);
        $adminPath = trim($adminPath, '/');

        SettingService::put('site.name', (string) $payload['site_name'], 'string', 'site', 'Nom public du site', true);
        SettingService::put('site.url', (string) $payload['site_url'], 'string', 'site', 'URL publique du site', true);
        SettingService::put('admin.path', $adminPath, 'string', 'admin', 'Chemin admin prefere', false);
        SettingService::put('admin.theme', (string) $payload['admin_theme'], 'string', 'admin', 'Theme admin prefere', false);
        SettingService::put('site.frontend_enabled', $this->toStringBool($payload['site_frontend_enabled']), 'boolean', 'site', 'Frontend public actif', true);
        SettingService::put('site.registration_open', $this->toStringBool($payload['registration_open']), 'boolean', 'site', 'Ouverture inscription publique', false);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Setting>
     */
    public function recentSettings()
    {
        return Setting::query()
            ->whereIn('key', [
                'site.name',
                'site.url',
                'admin.path',
                'admin.theme',
                'site.frontend_enabled',
                'site.registration_open',
            ])
            ->orderBy('group')
            ->orderBy('key')
            ->get();
    }

    private function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return ((int) $value) === 1;
        }

        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }

    private function toStringBool(mixed $value): string
    {
        return $this->toBool($value) ? '1' : '0';
    }
}
