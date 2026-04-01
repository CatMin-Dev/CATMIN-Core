<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class AddonRegistryService
{
    public function __construct(private readonly AddonPackageValidatorService $packageValidatorService)
    {
    }

    public static function registryPath(): string
    {
        return storage_path('app/addons/registry/index.json');
    }

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        File::ensureDirectoryExists(dirname(self::registryPath()));

        $packages = [];
        foreach (AddonMarketplaceService::listPackages() as $package) {
            $archivePath = AddonMarketplaceService::packagesPath() . '/' . $package['file'];
            $validated = $this->packageValidatorService->validateArchive($archivePath, (string) ($package['sha256'] ?? ''));
            $manifest = (array) ($validated['manifest'] ?? []);
            $slug = (string) ($manifest['slug'] ?? $package['slug'] ?? '');
            $installed = AddonManager::find($slug);
            $installedVersion = $installed ? (string) ($installed->version ?? '0.0.0') : '';

            $packages[] = [
                'slug' => $slug,
                'name' => (string) ($manifest['name'] ?? $package['slug'] ?? 'Addon'),
                'description' => (string) ($manifest['description'] ?? ''),
                'version' => (string) ($manifest['version'] ?? $package['version'] ?? '0.0.0'),
                'author' => (string) ($manifest['author'] ?? ''),
                'category' => (string) ($manifest['category'] ?? 'general'),
                'package_file' => (string) ($package['file'] ?? ''),
                'package_url' => (string) ($manifest['package_url'] ?? ''),
                'sha256' => (string) ($package['sha256'] ?? ''),
                'compatibility' => $validated['compatibility'] ?? [
                    'compatible' => false,
                    'status' => 'incompatible',
                    'warnings' => [],
                    'blockers' => [(string) ($validated['message'] ?? 'Validation package echouee.')],
                    'summary' => 'Incompatible bloquant',
                ],
                'required_modules' => (array) ($manifest['required_modules'] ?? []),
                'dependencies' => (array) ($manifest['dependencies'] ?? []),
                'docs_url' => (string) ($manifest['docs_url'] ?? ''),
                'homepage' => (string) ($manifest['homepage'] ?? ''),
                'install_notes' => (string) ($manifest['install_notes'] ?? ''),
                'permissions_declared' => (array) ($manifest['permissions_declared'] ?? []),
                'installed' => $installed !== null,
                'enabled' => (bool) ($installed->enabled ?? false),
                'installed_version' => $installedVersion,
                'update_available' => $installedVersion !== '' && VersioningService::compare($installedVersion, (string) ($manifest['version'] ?? '0.0.0')) < 0,
                'package_valid' => (bool) ($validated['ok'] ?? false),
            ];
        }

        $payload = [
            'generated_at' => now()->toIso8601String(),
            'registry_type' => 'local-json',
            'packages_count' => count($packages),
            'packages' => $packages,
            'installed_addons' => AddonManager::summary(),
        ];

        File::put(self::registryPath(), json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    public function read(): array
    {
        if (!File::exists(self::registryPath())) {
            return $this->build();
        }

        $decoded = json_decode((string) File::get(self::registryPath()), true);

        return is_array($decoded) ? $decoded : $this->build();
    }
}
