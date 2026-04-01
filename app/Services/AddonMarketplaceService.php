<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class AddonMarketplaceService
{
    public static function packagesPath(): string
    {
        return storage_path('app/addons/packages');
    }

    public static function indexPath(): string
    {
        return storage_path('app/addons/marketplace/index.json');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function listPackages(): array
    {
        $packagesPath = self::packagesPath();
        if (!File::isDirectory($packagesPath)) {
            return [];
        }

        $entries = [];
        foreach (File::files($packagesPath) as $file) {
            $name = $file->getFilename();
            if (!str_ends_with(strtolower($name), '.zip')) {
                continue;
            }

            $base = preg_replace('/\.zip$/i', '', $name) ?? $name;
            preg_match('/^(?<slug>.+)-(?<version>\d+\.\d+\.\d+)(?:-(?<stamp>\d{8}-\d{6}))?$/', $base, $matches);

            $entries[] = [
                'file' => $name,
                'slug' => (string) ($matches['slug'] ?? $base),
                'version' => (string) ($matches['version'] ?? '0.1.0'),
                'build' => (string) ($matches['stamp'] ?? ''),
                'size_bytes' => (int) $file->getSize(),
                'updated_at' => date(DATE_ATOM, (int) $file->getMTime()),
                'sha256' => hash_file('sha256', $file->getPathname()) ?: '',
            ];
        }

        usort($entries, fn ($a, $b) => strcmp((string) $b['updated_at'], (string) $a['updated_at']));

        return $entries;
    }

    /**
     * @return array<string, mixed>
     */
    public static function buildIndex(): array
    {
        $registry = app(AddonRegistryService::class)->build();
        $registry['packages_path'] = self::packagesPath();

        File::ensureDirectoryExists(dirname(self::indexPath()));
        File::put(self::indexPath(), json_encode($registry, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");

        return $registry;
    }

    /**
     * @return array<string, mixed>
     */
    public static function readIndex(): array
    {
        $path = self::indexPath();
        if (!File::exists($path)) {
            return self::buildIndex();
        }

        $decoded = json_decode((string) File::get($path), true);

        return is_array($decoded) ? $decoded : self::buildIndex();
    }
}
