<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Carbon\Carbon;

/**
 * ModuleVersionManager
 *
 * Manages semantic versioning for modules during development.
 * Tracks modification dates and automatically increments versions.
 * Supports V2-dev tagging for beta features.
 */
class ModuleVersionManager
{
    /**
     * Get path to modules directory.
     */
    private static function getModulePath(): string
    {
        return base_path('modules');
    }

    /**
     * Get path to version log file.
     */
    private static function getVersionLogPath(): string
    {
        return storage_path('logs/module-versions.json');
    }

    /**
     * Increment module to next version (patch/minor/major).
     *
     * @param string $moduleName Module slug (lowercase, e.g., 'shop')
     * @param string $type 'patch' | 'minor' | 'major' (default: 'patch')
     * @param string|null $tag Beta tag (e.g., 'dev', 'beta1')
     * @return string New version or null on error
     */
    public static function increment(string $moduleName, string $type = 'patch', ?string $tag = null): ?string
    {
        $moduleJsonPath = self::getModuleJsonPath($moduleName);

        if (!$moduleJsonPath || !File::exists($moduleJsonPath)) {
            return null;
        }

        $json = json_decode(File::get($moduleJsonPath), true);
        $currentVersion = $json['version'] ?? '1.0.0';

        // Parse current version base (strip any prerelease suffix)
        $baseVersion = preg_replace('/-.+$/', '', (string) $currentVersion) ?: '1.0.0';
        $parts = explode('.', $baseVersion);
        $major = (int) ($parts[0] ?? 1);
        $minor = (int) ($parts[1] ?? 0);
        $patch = (int) ($parts[2] ?? 0);

        // Increment based on type
        match ($type) {
            'major' => $major++,
            'minor' => [$minor++, $patch = 0],
            'patch' => $patch++,
        };

        $newVersion = "{$major}.{$minor}.{$patch}";

        // Add tag if specified (e.g., 2.0.0-dev)
        if ($tag) {
            $newVersion .= "-{$tag}";
        }

        // Update module.json
        $json['version'] = $newVersion;
        $json['updated_at'] = Carbon::now()->toIso8601String();

        File::put($moduleJsonPath, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");

        // Log version change
        self::logVersionChange($moduleName, $currentVersion, $newVersion);

        return $newVersion;
    }

    /**
     * Set module to specific version with optional tag.
     *
     * @param string $moduleName Module slug
     * @param string $version Target version (e.g., '2.0.0', '2.0.0-dev')
     * @return bool Success status
     */
    public static function set(string $moduleName, string $version): bool
    {
        $moduleJsonPath = self::getModuleJsonPath($moduleName);

        if (!$moduleJsonPath || !File::exists($moduleJsonPath)) {
            return false;
        }

        if (!VersioningService::isValid($version)) {
            return false;
        }

        $json = json_decode(File::get($moduleJsonPath), true);
        $oldVersion = $json['version'] ?? '1.0.0';

        $json['version'] = $version;
        $json['updated_at'] = Carbon::now()->toIso8601String();

        File::put($moduleJsonPath, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");

        self::logVersionChange($moduleName, $oldVersion, $version);

        return true;
    }

    /**
     * Get current version of module.
     */
    public static function getVersion(string $moduleName): ?string
    {
        $moduleJsonPath = self::getModuleJsonPath($moduleName);

        if (!$moduleJsonPath || !File::exists($moduleJsonPath)) {
            return null;
        }

        $json = json_decode(File::get($moduleJsonPath), true);

        return $json['version'] ?? null;
    }

    /**
     * Get all module versions as array.
     *
     * @return array ['module_slug' => 'version', ...]
     */
    public static function getAllVersions(): array
    {
        $versions = [];
        $modules = File::directories(self::getModulePath());

        foreach ($modules as $modulePath) {
            $moduleName = basename($modulePath);
            $moduleJsonPath = $modulePath . '/module.json';

            if (File::exists($moduleJsonPath)) {
                $json = json_decode(File::get($moduleJsonPath), true);
                $versions[strtolower($moduleName ?? '')] = $json['version'] ?? 'unknown';
            }
        }

        ksort($versions);

        return $versions;
    }

    /**
     * Get version changelog for a module.
     */
    public static function getChangelog(string $moduleName): array
    {
        $log = self::readVersionLog();

        return array_filter($log, fn($entry) => strtolower($entry['module'] ?? '') === strtolower($moduleName));
    }

    /**
     * Get admin dashboard version.
     */
    public static function getDashboardVersion(): string
    {
        return config('app.dashboard_version', '1.0.0');
    }

    /**
     * Set admin dashboard version.
     */
    public static function setDashboardVersion(string $version): void
    {
        if (!VersioningService::isValid($version)) {
            return;
        }

        $envPath = base_path('.env');

        if (!File::exists($envPath)) {
            return;
        }

        $env = File::get($envPath);
        $pattern = '/DASHBOARD_VERSION=.*/';

        if (preg_match($pattern, $env)) {
            $env = preg_replace($pattern, 'DASHBOARD_VERSION=' . $version, $env);
        } else {
            $env .= "\nDASHBOARD_VERSION={$version}\n";
        }

        File::put($envPath, $env);

        // Update config cache
        config(['app.dashboard_version' => $version]);
    }

    /**
     * Get path to module.json for a module.
     */
    private static function getModuleJsonPath(string $moduleName): ?string
    {
        $searchDir = self::getModulePath();
        $modules = File::directories($searchDir);

        foreach ($modules as $modulePath) {
            if (strtolower(basename($modulePath)) === strtolower($moduleName)) {
                return $modulePath . '/module.json';
            }
        }

        return null;
    }

    /**
     * Log a version change to version history file.
     */
    private static function logVersionChange(string $module, string $from, string $to): void
    {
        $log = self::readVersionLog();

        $log[] = [
            'timestamp' => Carbon::now()->toIso8601String(),
            'module' => strtolower($module),
            'from' => $from,
            'to' => $to,
            'phase' => config('app.development_phase', 'v2-dev'),
        ];

        // Keep last 1000 entries
        if (count($log) > 1000) {
            $log = array_slice($log, -1000);
        }

        $versionLogPath = self::getVersionLogPath();
        File::ensureDirectoryExists(dirname($versionLogPath));
        File::put($versionLogPath, json_encode($log, JSON_PRETTY_PRINT) . "\n");
    }

    /**
     * Read version change log.
     */
    private static function readVersionLog(): array
    {
        $versionLogPath = self::getVersionLogPath();

        if (!File::exists($versionLogPath)) {
            return [];
        }

        $content = File::get($versionLogPath);

        return json_decode($content, true) ?? [];
    }

    /**
     * Generate version matrix report for all modules + dashboard.
     *
     * @return array Structured report
     */
    public static function generateMatrix(): array
    {
        return [
            'generated_at' => Carbon::now()->toIso8601String(),
            'development_phase' => config('app.development_phase', 'v2-dev'),
            'dashboard_version' => self::getDashboardVersion(),
            'modules' => self::getAllVersions(),
            'total_modules' => count(self::getAllVersions()),
        ];
    }
}
