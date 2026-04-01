<?php

namespace App\Console\Commands;

use App\Services\AddonManager;
use App\Services\AddonMarketplaceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

class CatminAddonPackageCommand extends Command
{
    protected $signature = 'catmin:addon:package
        {slug : Slug de l\'addon a empaqueter}
        {--output= : Dossier de sortie (defaut: storage/app/addons/packages)}
        {--format=zip : Format d\'archive (zip uniquement en V1)}';

    protected $description = 'Construit une archive distribuable d\'un addon CATMIN';

    public function handle(): int
    {
        $slug = (string) $this->argument('slug');
        $format = strtolower((string) $this->option('format'));

        if ($format !== 'zip') {
            $this->error('Format non supporte. Utiliser --format=zip.');
            return self::FAILURE;
        }

        if (!class_exists(ZipArchive::class)) {
            $this->error('Extension PHP zip absente. Impossible de generer l\'archive.');
            return self::FAILURE;
        }

        $addon = AddonManager::find($slug);
        if ($addon === null) {
            $this->error("Addon introuvable: {$slug}");
            return self::FAILURE;
        }

        $missing = AddonManager::missingStructure($addon);
        if ($missing !== []) {
            $this->error('Structure addon incomplete: ' . implode(', ', $missing));
            return self::FAILURE;
        }

        $outputDir = (string) ($this->option('output') ?: storage_path('app/addons/packages'));
        File::ensureDirectoryExists($outputDir);

        $safeVersion = preg_replace('/[^0-9A-Za-z._-]+/', '-', (string) ($addon->version ?? '0.0.0')) ?: '0.0.0';
        $archiveName = sprintf('%s-%s-%s.zip', (string) $addon->slug, $safeVersion, now()->format('Ymd-His'));
        $archivePath = rtrim($outputDir, '/\\') . DIRECTORY_SEPARATOR . $archiveName;

        $zip = new ZipArchive();
        if ($zip->open($archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->error('Impossible de creer l\'archive zip.');
            return self::FAILURE;
        }

        $sourcePath = (string) $addon->path;
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourcePath, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            $fullPath = (string) $file->getPathname();
            $relative = ltrim(str_replace($sourcePath, '', $fullPath), '/\\');

            if ($relative === '' || str_starts_with($relative, '.git/')) {
                continue;
            }

            if ($file->isDir()) {
                $zip->addEmptyDir($relative);
            } else {
                $zip->addFile($fullPath, $relative);
            }
        }

        $zip->close();

        $checksum = hash_file('sha256', $archivePath) ?: '';
        File::put($archivePath . '.sha256', $checksum . PHP_EOL);
        AddonMarketplaceService::buildIndex();

        $this->info('Archive addon generee.');
        $this->line('Addon: ' . (string) $addon->slug);
        $this->line('Version: ' . (string) ($addon->version ?? 'unknown'));
        $this->line('Fichier: ' . $archivePath);
        $this->line('SHA-256: ' . $checksum);

        return self::SUCCESS;
    }
}
