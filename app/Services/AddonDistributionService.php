<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use ZipArchive;

class AddonDistributionService
{
    private const LOG_FILE = 'logs/addon-distribution.jsonl';

    public function __construct(private readonly AddonPackageValidatorService $packageValidatorService)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function installLocalAddon(string $slug, bool $enable = true, bool $migrate = true): array
    {
        $addon = AddonManager::find($slug);
        if ($addon === null) {
            return ['ok' => false, 'message' => 'Addon introuvable: ' . $slug];
        }

        $validation = AddonManager::canEnable($slug);
        if (($validation['allowed'] ?? false) !== true) {
            return ['ok' => false, 'message' => (string) ($validation['message'] ?? 'Addon incompatible.')];
        }

        $steps = [];

        if ($enable) {
            if (!AddonManager::enable($slug)) {
                return ['ok' => false, 'message' => 'Impossible d activer l addon.'];
            }
            $steps[] = 'enabled';
        }

        if ($migrate) {
            $migrationResult = AddonMigrationRunner::runForAddon($slug);
            $steps[] = 'migrated:' . (string) ($migrationResult['ran'] ?? 0);
        }

        $this->log('install_local', [
            'slug' => $slug,
            'enable' => $enable,
            'migrate' => $migrate,
            'steps' => $steps,
        ]);

        return [
            'ok' => true,
            'message' => 'Addon installe depuis le dossier local.',
            'slug' => $slug,
            'steps' => $steps,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function installPackage(string $archivePath, bool $enable = true, bool $migrate = true, ?string $expectedChecksum = null): array
    {
        $validated = $this->packageValidatorService->validateArchive($archivePath, $expectedChecksum);
        if (($validated['ok'] ?? false) !== true) {
            return $validated;
        }

        $manifest = (array) ($validated['manifest'] ?? []);
        $slug = (string) ($manifest['slug'] ?? '');
        if ($slug === '') {
            return ['ok' => false, 'message' => 'Slug addon manquant dans le manifest.'];
        }

        $targetPath = base_path((string) config('catmin.addons.path', 'addons') . '/' . $slug);
        $backupPath = '';

        if (File::exists($targetPath)) {
            $backupPath = storage_path('app/addons/backups/' . $slug . '-' . now()->format('Ymd-His'));
            File::ensureDirectoryExists(dirname($backupPath));
            File::copyDirectory($targetPath, $backupPath);
            File::deleteDirectory($targetPath);
        }

        $extract = $this->extractArchive($archivePath, $targetPath);
        if (($extract['ok'] ?? false) !== true) {
            return $extract;
        }

        AddonManager::reload();

        $result = $this->installLocalAddon($slug, $enable, $migrate);
        $result['backup_path'] = $backupPath;
        $result['checksum'] = (string) ($validated['checksum'] ?? '');

        $this->log('install_package', [
            'slug' => $slug,
            'archive' => $archivePath,
            'backup_path' => $backupPath,
            'result' => $result,
        ]);

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private function extractArchive(string $archivePath, string $targetPath): array
    {
        $tmpPath = storage_path('app/addons/tmp-' . now()->format('Ymd-His'));
        File::ensureDirectoryExists($tmpPath);

        $zip = new ZipArchive();
        if ($zip->open($archivePath) !== true) {
            return ['ok' => false, 'message' => 'Impossible d ouvrir l archive addon.'];
        }

        $zip->extractTo($tmpPath);
        $zip->close();

        $root = $this->detectRoot($tmpPath);
        File::ensureDirectoryExists($targetPath);
        File::copyDirectory($root, $targetPath);
        File::deleteDirectory($tmpPath);

        return ['ok' => true, 'message' => 'Archive extraite.'];
    }

    private function detectRoot(string $tmpPath): string
    {
        $directories = File::directories($tmpPath);
        $files = File::files($tmpPath);

        if (count($directories) === 1 && count($files) === 0) {
            return $directories[0];
        }

        return $tmpPath;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function log(string $event, array $payload): void
    {
        $path = storage_path(self::LOG_FILE);
        File::ensureDirectoryExists(dirname($path));
        File::append($path, json_encode([
            'timestamp' => now()->toIso8601String(),
            'event' => $event,
            'payload' => $payload,
        ], JSON_UNESCAPED_SLASHES) . PHP_EOL);
    }
}
