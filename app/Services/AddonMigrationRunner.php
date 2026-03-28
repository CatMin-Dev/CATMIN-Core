<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

/**
 * AddonMigrationRunner
 *
 * Executes addon-specific migrations located under addons/<slug>/Migrations.
 */
class AddonMigrationRunner
{
    /**
     * @return array{
     *   name: string,
     *   slug: string,
     *   declared_version: string,
     *   installed_version: string,
     *   has_pending: bool,
     *   has_version_upgrade: bool,
     *   migrations_path: string,
     *   has_migrations: bool
     * }
     */
    public static function statusForAddon(string $slug): array
    {
        $addon = AddonManager::find($slug);

        if ($addon === null) {
            return [
                'name' => $slug,
                'slug' => $slug,
                'declared_version' => '',
                'installed_version' => '',
                'has_pending' => false,
                'has_version_upgrade' => false,
                'migrations_path' => '',
                'has_migrations' => false,
            ];
        }

        $migrationsPath = $addon->path . '/Migrations';
        $hasMigrations = File::exists($migrationsPath) && count(File::files($migrationsPath)) > 0;
        $installedVersionRaw = self::getInstalledVersion($slug);
        $installedVersion = VersioningService::isValid($installedVersionRaw)
            ? VersioningService::normalize($installedVersionRaw)
            : '';

        return [
            'name' => (string) ($addon->name ?? $slug),
            'slug' => (string) $addon->slug,
            'declared_version' => VersioningService::normalize((string) ($addon->version ?? '')),
            'installed_version' => $installedVersion,
            'has_pending' => $hasMigrations ? self::hasPending($slug) : false,
            'has_version_upgrade' => self::hasVersionUpgrade($slug),
            'migrations_path' => $migrationsPath,
            'has_migrations' => $hasMigrations,
        ];
    }

    /**
     * Run all pending migrations for a given addon slug.
     *
     * @return array{ran: int, output: string}
     */
    public static function runForAddon(string $slug): array
    {
        $addon = AddonManager::find($slug);

        if ($addon === null) {
            return ['ran' => 0, 'output' => "Addon '{$slug}' not found."];
        }

        $migrationsPath = $addon->path . '/Migrations';

        if (!File::exists($migrationsPath) || count(File::files($migrationsPath)) === 0) {
            return ['ran' => 0, 'output' => 'No migrations folder.'];
        }

        $exitCode = Artisan::call('migrate', [
            '--path' => 'addons/' . $addon->directory . '/Migrations',
            '--force' => true,
        ]);

        $output = Artisan::output();

        if ($exitCode === 0) {
            self::storeInstalledVersion($slug, VersioningService::normalize((string) ($addon->version ?? '')));
        }

        $ran = substr_count($output, 'DONE') + substr_count($output, 'Running');

        return ['ran' => $ran, 'output' => trim($output)];
    }

    /**
     * Check whether the addon has pending migrations.
     */
    public static function hasPending(string $slug): bool
    {
        $addon = AddonManager::find($slug);

        if ($addon === null) {
            return false;
        }

        $migrationsPath = $addon->path . '/Migrations';

        if (!File::exists($migrationsPath)) {
            return false;
        }

        try {
            $exitCode = Artisan::call('migrate:status', [
                '--path' => 'addons/' . $addon->directory . '/Migrations',
            ]);

            if ($exitCode !== 0) {
                return false;
            }

            return str_contains(Artisan::output(), 'Pending');
        } catch (\Throwable) {
            return false;
        }
    }

    public static function hasVersionUpgrade(string $slug): bool
    {
        $addon = AddonManager::find($slug);

        if ($addon === null) {
            return false;
        }

        $currentVersion = VersioningService::normalize((string) ($addon->version ?? ''));
        $installedVersionRaw = self::getInstalledVersion($slug);

        if ($installedVersionRaw === '') {
            return false;
        }

        $installedVersion = VersioningService::normalize($installedVersionRaw);

        return VersioningService::isUpgrade($installedVersion, $currentVersion);
    }

    /**
     * Roll back addon migrations.
     *
     * @return array{rolled_back: bool, output: string}
     */
    public static function rollbackForAddon(string $slug, int $steps = 0): array
    {
        $addon = AddonManager::find($slug);

        if ($addon === null) {
            return ['rolled_back' => false, 'output' => "Addon '{$slug}' not found."];
        }

        $migrationsPath = $addon->path . '/Migrations';

        if (!File::exists($migrationsPath) || count(File::files($migrationsPath)) === 0) {
            return ['rolled_back' => false, 'output' => 'No migrations folder.'];
        }

        $args = [
            '--path' => 'addons/' . $addon->directory . '/Migrations',
            '--force' => true,
        ];

        if ($steps > 0) {
            $args['--step'] = $steps;
        }

        $exitCode = Artisan::call('migrate:rollback', $args);
        $output = trim(Artisan::output());

        return [
            'rolled_back' => $exitCode === 0,
            'output' => $output,
        ];
    }

    /**
     * @return array{upgraded: bool, ran: int, output: string}
     */
    public static function upgradeIfNeeded(string $slug): array
    {
        if (!self::hasVersionUpgrade($slug)) {
            return ['upgraded' => false, 'ran' => 0, 'output' => 'Already up to date.'];
        }

        $result = self::runForAddon($slug);

        return array_merge($result, ['upgraded' => true]);
    }

    public static function storeInstalledVersion(string $slug, string $version): void
    {
        $version = VersioningService::normalize($version);

        try {
            SettingService::put(
                'addon.' . $slug . '.installed_version',
                $version,
                'string',
                'system',
                'Installed version for addon ' . $slug,
                false
            );
        } catch (\Throwable) {
            // Settings table may not exist during early bootstrap.
        }
    }

    public static function getInstalledVersion(string $slug): string
    {
        try {
            return (string) SettingService::get('addon.' . $slug . '.installed_version', '');
        } catch (\Throwable) {
            return '';
        }
    }
}
