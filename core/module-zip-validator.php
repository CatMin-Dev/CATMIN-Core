<?php

declare(strict_types=1);

require_once CATMIN_CORE . '/module-manifest-standard.php';

final class CoreModuleZipValidator
{
    public function validateArchive(string $zipPath): array
    {
        $errors = [];
        if (!is_file($zipPath)) {
            return ['ok' => false, 'errors' => ['ZIP introuvable']];
        }
        if (strtolower(pathinfo($zipPath, PATHINFO_EXTENSION)) !== 'zip') {
            return ['ok' => false, 'errors' => ['Format non ZIP']];
        }
        if (!class_exists('ZipArchive')) {
            return ['ok' => false, 'errors' => ['ZipArchive indisponible']];
        }
        if (filesize($zipPath) > 50 * 1024 * 1024) {
            return ['ok' => false, 'errors' => ['ZIP trop volumineux (>50MB)']];
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return ['ok' => false, 'errors' => ['ZIP non lisible']];
        }

        $moduleCandidates = [];
        $manifestEntry = null;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = str_replace('\\', '/', (string) $zip->getNameIndex($i));
            if ($entry === '') {
                continue;
            }
            if (str_starts_with($entry, '/') || str_contains($entry, '../')) {
                $errors[] = 'Path traversal detecte: ' . $entry;
                continue;
            }
            foreach (['.env', '.htaccess', 'composer.lock'] as $forbidden) {
                if (str_ends_with($entry, '/' . $forbidden) || $entry === $forbidden) {
                    $errors[] = 'Fichier interdit dans package: ' . $entry;
                }
            }
            if (preg_match('#(^|/)manifest\.json$#', $entry) === 1) {
                $manifestEntry = $entry;
                $parts = explode('/', $entry);
                array_pop($parts);
                if ($parts !== []) {
                    $moduleCandidates[] = implode('/', $parts);
                }
            }
        }

        $zip->close();

        if ($manifestEntry === null) {
            $errors[] = 'manifest.json absent';
        }
        $moduleCandidates = array_values(array_unique($moduleCandidates));
        if (count($moduleCandidates) > 1) {
            $errors[] = 'Archive multi-modules non autorisee';
        }

        return [
            'ok' => $errors === [],
            'errors' => $errors,
            'manifest_entry' => $manifestEntry,
            'module_root' => $moduleCandidates[0] ?? '',
        ];
    }

    public function readManifestFromExtracted(string $moduleRoot): array
    {
        $manifestFile = rtrim($moduleRoot, '/') . '/manifest.json';
        if (!is_file($manifestFile)) {
            return ['ok' => false, 'errors' => ['manifest.json absent apres extraction'], 'manifest' => []];
        }
        $manifest = json_decode((string) file_get_contents($manifestFile), true);
        if (!is_array($manifest)) {
            return ['ok' => false, 'errors' => ['manifest.json invalide'], 'manifest' => []];
        }
        $std = new CoreModuleManifestStandard();
        $normalized = $std->normalize($manifest);
        $validation = $std->validate($normalized);
        return [
            'ok' => (bool) ($validation['valid'] ?? false),
            'errors' => (array) ($validation['errors'] ?? []),
            'manifest' => $normalized,
        ];
    }
}

