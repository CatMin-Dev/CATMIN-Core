<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

/**
 * ModuleMigrationRunner
 *
 * Runs and tracks module-specific database migrations.
 *
 * - On enable: runs all pending migrations for the module.
 * - On update: compares stored installed_version with module.json version;
 *   if they differ, runs migrations then stores the new version.
 */
class ModuleMigrationRunner
{
    /**
     * Run all pending migrations for the given module slug.
     * Safe to call multiple times: Laravel tracks already-run migration files.
     *
     * @return array{ran: int, output: string}
     */
    public static function runForModule(string $slug): array
    {
        $module = ModuleManager::find($slug);

        if ($module === null) {
            return ['ran' => 0, 'output' => "Module '{$slug}' not found."];
        }

        $migrationsPath = base_path('modules/' . $module->directory . '/Migrations');

        if (!File::exists($migrationsPath) || count(File::files($migrationsPath)) === 0) {
            return ['ran' => 0, 'output' => 'No migrations folder.'];
        }

        $exitCode = Artisan::call('migrate', [
            '--path' => 'modules/' . $module->directory . '/Migrations',
            '--force' => true,
        ]);

        $output = Artisan::output();

        if ($exitCode === 0) {
            self::storeInstalledVersion($slug, VersioningService::normalize((string) ($module->version ?? '')));
        }

        $ran = substr_count($output, 'DONE') + substr_count($output, 'Running');

        return ['ran' => $ran, 'output' => trim($output)];
    }

    /**
     * Check whether the module has pending (unrun) migrations.
     */
    public static function hasPending(string $slug): bool
    {
        $module = ModuleManager::find($slug);

        if ($module === null) {
            return false;
        }

        $migrationsPath = base_path('modules/' . $module->directory . '/Migrations');

        if (!File::exists($migrationsPath)) {
            return false;
        }

        try {
            $exitCode = Artisan::call('migrate:status', [
                '--path' => 'modules/' . $module->directory . '/Migrations',
            ]);

            if ($exitCode !== 0) {
                return false;
            }

            return str_contains(Artisan::output(), 'Pending');
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Detect whether the module has a pending version upgrade, i.e. installed
     * version differs from the declared version in module.json.
     */
    public static function hasVersionUpgrade(string $slug): bool
    {
        $module = ModuleManager::find($slug);

        if ($module === null) {
            return false;
        }

        $currentVersion = VersioningService::normalize((string) ($module->version ?? ''));
        $installedVersionRaw = self::getInstalledVersion($slug);

        if ($installedVersionRaw === '') {
            return false;
        }

        $installedVersion = VersioningService::normalize($installedVersionRaw);

        return VersioningService::isUpgrade($installedVersion, $currentVersion);
    }

    /**
     * Run migrations for a module if its version has changed since last install.
     * Does nothing when already up-to-date.
     *
     * @return array{upgraded: bool, ran: int, output: string}
     */
    public static function upgradeIfNeeded(string $slug): array
    {
        if (!self::hasVersionUpgrade($slug)) {
            return ['upgraded' => false, 'ran' => 0, 'output' => 'Already up to date.'];
        }

        $result = self::runForModule($slug);

        return array_merge($result, ['upgraded' => true]);
    }

    /**
     * Store the installed version for a module in the settings table.
     */
    public static function storeInstalledVersion(string $slug, string $version): void
    {
        $version = VersioningService::normalize($version);

        try {
            SettingService::put(
                'module.' . $slug . '.installed_version',
                $version,
                'string',
                'system',
                'Installed version for module ' . $slug,
                false
            );
        } catch (\Throwable) {
            // Settings table may not exist during early bootstrap — fail silently.
        }
    }

    /**
     * Get the stored installed version for a module (empty string if unknown).
     */
    public static function getInstalledVersion(string $slug): string
    {
        try {
            return (string) SettingService::get('module.' . $slug . '.installed_version', '');
        } catch (\Throwable) {
            return '';
        }
    }
}
