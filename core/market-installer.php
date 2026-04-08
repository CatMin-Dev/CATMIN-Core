<?php

declare(strict_types=1);

require_once CATMIN_CORE . '/updater-validator.php';
require_once CATMIN_CORE . '/updater-github.php';
require_once CATMIN_CORE . '/module-validator.php';
require_once CATMIN_CORE . '/module-compatibility-checker.php';
require_once CATMIN_CORE . '/module-activator.php';

final class CoreMarketInstaller
{
    public function installFromCatalogItem(array $item): array
    {
        $zipUrl = trim((string) ($item['zip_url'] ?? ''));
        $scope = strtolower(trim((string) ($item['scope'] ?? '')));
        $slug = strtolower(trim((string) ($item['slug'] ?? '')));
        $pathInZip = trim((string) ($item['path_in_zip'] ?? ''));

        if ($zipUrl === '' || $scope === '' || $slug === '' || $pathInZip === '') {
            return ['ok' => false, 'message' => 'Données catalogue incomplètes.', 'errors' => ['scope/slug/zip/path manquants']];
        }

        $downloads = CATMIN_STORAGE . '/updates/downloads';
        $staging = CATMIN_STORAGE . '/updates/staging/market-' . date('Ymd-His') . '-' . $slug;
        if (!is_dir($downloads)) {
            @mkdir($downloads, 0775, true);
        }
        if (!is_dir($staging)) {
            @mkdir($staging, 0775, true);
        }

        $zipPath = $downloads . '/market-' . $scope . '-' . $slug . '-' . date('YmdHis') . '.zip';
        $dl = (new CoreUpdaterGithub())->downloadAsset($zipUrl, $zipPath);
        if (!($dl['ok'] ?? false)) {
            return ['ok' => false, 'message' => (string) ($dl['error'] ?? 'Téléchargement module KO.'), 'errors' => [(string) ($dl['error'] ?? 'download')]];
        }

        $validation = (new CoreUpdaterValidator())->validateZip($zipPath, false);
        if (!($validation['ok'] ?? false)) {
            return ['ok' => false, 'message' => 'ZIP module invalide.', 'errors' => (array) ($validation['errors'] ?? [])];
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return ['ok' => false, 'message' => 'Ouverture ZIP module impossible.', 'errors' => ['zip_open']];
        }
        if (!$zip->extractTo($staging)) {
            $zip->close();
            return ['ok' => false, 'message' => 'Extraction ZIP module impossible.', 'errors' => ['zip_extract']];
        }
        $zip->close();

        $moduleSource = $staging . '/' . $pathInZip;
        $manifestFile = $moduleSource . '/manifest.json';
        if (!is_file($manifestFile)) {
            return ['ok' => false, 'message' => 'Manifest module introuvable après extraction.', 'errors' => ['manifest_missing']];
        }

        $manifest = json_decode((string) file_get_contents($manifestFile), true);
        if (!is_array($manifest)) {
            return ['ok' => false, 'message' => 'Manifest module invalide.', 'errors' => ['manifest_invalid']];
        }

        $validator = new CoreModuleValidator();
        $valid = $validator->validate($manifest, $moduleSource);
        if (!(bool) ($valid['valid'] ?? false)) {
            return ['ok' => false, 'message' => 'Validation module KO.', 'errors' => (array) ($valid['errors'] ?? [])];
        }

        $compat = (new CoreModuleCompatibilityChecker())->check($manifest);
        if (!(bool) ($compat['compatible'] ?? false)) {
            return ['ok' => false, 'message' => 'Compatibilité core KO.', 'errors' => (array) ($compat['errors'] ?? [])];
        }

        $destDir = CATMIN_MODULES . '/' . $scope . '/' . $slug;
        if (is_dir($destDir)) {
            $this->deleteDir($destDir);
        }
        if (!is_dir(dirname($destDir))) {
            @mkdir(dirname($destDir), 0775, true);
        }
        if (!$this->copyDir($moduleSource, $destDir)) {
            return ['ok' => false, 'message' => 'Copie module impossible.', 'errors' => ['copy_failed']];
        }

        $activation = (new CoreModuleActivator())->activate($scope, $slug);
        if (!(bool) ($activation['ok'] ?? false)) {
            return ['ok' => false, 'message' => 'Module copié mais activation KO.', 'errors' => [(string) ($activation['message'] ?? 'activation_failed')]];
        }

        return ['ok' => true, 'message' => 'Module installé et activé.', 'errors' => []];
    }

    private function copyDir(string $source, string $dest): bool
    {
        if (!is_dir($source)) {
            return false;
        }
        if (!is_dir($dest) && !@mkdir($dest, 0775, true) && !is_dir($dest)) {
            return false;
        }

        $items = scandir($source);
        if (!is_array($items)) {
            return false;
        }
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $src = $source . '/' . $item;
            $dst = $dest . '/' . $item;
            if (is_dir($src)) {
                if (!$this->copyDir($src, $dst)) {
                    return false;
                }
            } else {
                if (!@copy($src, $dst)) {
                    return false;
                }
            }
        }

        return true;
    }

    private function deleteDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $item) {
            if ($item->isDir()) {
                @rmdir($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }
        @rmdir($dir);
    }
}
