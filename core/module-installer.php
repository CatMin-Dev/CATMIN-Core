<?php

declare(strict_types=1);

require_once CATMIN_CORE . '/module-install-runner.php';
require_once CATMIN_CORE . '/updater-github.php';

final class CoreModuleInstaller
{
    public function installFromZip(string $zipPath, bool $activate = true): array
    {
        return (new CoreModuleInstallRunner())->installZip($zipPath, $activate);
    }

    public function installFromMarket(array $catalogItem, bool $activate = true): array
    {
        $zipUrl = trim((string) ($catalogItem['zip_url'] ?? ''));
        if ($zipUrl === '') {
            return ['ok' => false, 'message' => 'URL ZIP market manquante', 'errors' => ['zip_url_missing']];
        }

        $scope = strtolower(trim((string) ($catalogItem['scope'] ?? 'module')));
        $slug = strtolower(trim((string) ($catalogItem['slug'] ?? 'module')));
        $pathInZip = ltrim(str_replace('\\', '/', trim((string) ($catalogItem['path_in_zip'] ?? ''))), '/');
        if ($pathInZip === '' || str_contains($pathInZip, '../')) {
            return ['ok' => false, 'message' => 'Chemin module market invalide', 'errors' => ['path_in_zip_invalid']];
        }

        $incomingDir = CATMIN_STORAGE . '/modules/incoming';
        $stagingDir = CATMIN_STORAGE . '/modules/staging';
        if (!is_dir($incomingDir)) {
            @mkdir($incomingDir, 0775, true);
        }
        if (!is_dir($stagingDir)) {
            @mkdir($stagingDir, 0775, true);
        }

        $stamp = gmdate('YmdHis');
        $repoZipPath = $incomingDir . '/market-repo-' . $scope . '-' . $slug . '-' . $stamp . '.zip';
        $moduleZipPath = $incomingDir . '/market-module-' . $scope . '-' . $slug . '-' . $stamp . '.zip';
        $dl = (new CoreUpdaterGithub())->downloadAsset($zipUrl, $repoZipPath);
        if (!(bool) ($dl['ok'] ?? false)) {
            return ['ok' => false, 'message' => (string) ($dl['error'] ?? 'Download market KO'), 'errors' => [(string) ($dl['error'] ?? 'download_failed')]];
        }

        if (!class_exists('ZipArchive')) {
            return ['ok' => false, 'message' => 'ZipArchive indisponible', 'errors' => ['zip_extension_missing']];
        }

        $extractDir = $stagingDir . '/market-' . $scope . '-' . $slug . '-' . $stamp;
        if (!is_dir($extractDir) && !@mkdir($extractDir, 0775, true) && !is_dir($extractDir)) {
            return ['ok' => false, 'message' => 'Staging market indisponible', 'errors' => ['staging_create_failed']];
        }

        $zip = new ZipArchive();
        if ($zip->open($repoZipPath) !== true || !$zip->extractTo($extractDir)) {
            if ($zip instanceof ZipArchive) {
                $zip->close();
            }
            $this->cleanupPath($extractDir);
            return ['ok' => false, 'message' => 'Extraction ZIP market impossible', 'errors' => ['extract_market_failed']];
        }
        $zip->close();

        $moduleSource = $extractDir . '/' . $pathInZip;
        if (!is_dir($moduleSource) || !is_file($moduleSource . '/manifest.json')) {
            $this->cleanupPath($extractDir);
            return ['ok' => false, 'message' => 'Module cible introuvable dans archive market', 'errors' => ['module_path_missing']];
        }

        $pack = new ZipArchive();
        if ($pack->open($moduleZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->cleanupPath($extractDir);
            return ['ok' => false, 'message' => 'Création ZIP module impossible', 'errors' => ['module_zip_create_failed']];
        }

        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($moduleSource, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($it as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $sourcePath = str_replace('\\', '/', $file->getPathname());
            $relative = ltrim(str_replace(str_replace('\\', '/', $moduleSource), '', $sourcePath), '/');
            if ($relative === '') {
                continue;
            }
            $pack->addFile($sourcePath, $relative);
        }
        $pack->close();
        $this->cleanupPath($extractDir);

        return $this->installFromZip($moduleZipPath, $activate);
    }

    private function cleanupPath(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $item) {
            if ($item->isDir()) {
                @rmdir($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }
        @rmdir($path);
    }
}
