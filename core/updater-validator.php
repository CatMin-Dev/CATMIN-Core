<?php

declare(strict_types=1);

final class CoreUpdaterValidator
{
    public function validateZip(string $zipPath, bool $requireVersionJson = true): array
    {
        if (!is_file($zipPath)) {
            return ['ok' => false, 'errors' => ['ZIP introuvable.']];
        }
        if (strtolower(pathinfo($zipPath, PATHINFO_EXTENSION)) !== 'zip') {
            return ['ok' => false, 'errors' => ['Format ZIP invalide.']];
        }
        if (!class_exists('ZipArchive')) {
            return ['ok' => false, 'errors' => ['Extension zip manquante.']];
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return ['ok' => false, 'errors' => ['Ouverture ZIP impossible.']];
        }

        $errors = [];
        $root = null;
        $versionEntry = null;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string) $zip->getNameIndex($i);
            if ($name === '') {
                continue;
            }
            $name = str_replace('\\', '/', $name);
            if (str_starts_with($name, '/') || str_contains($name, '../') || str_contains($name, '..\\')) {
                $errors[] = 'Entrée ZIP suspecte: ' . $name;
                continue;
            }
            $parts = explode('/', $name);
            $first = trim((string) ($parts[0] ?? ''));
            if ($first !== '' && $root === null) {
                $root = $first;
            }
            if (preg_match('#(^|/)version\.json$#', $name) === 1) {
                $versionEntry = $name;
            }
        }

        $zip->close();

        if ($requireVersionJson && $versionEntry === null) {
            $errors[] = 'version.json manquant dans le package.';
        }

        return [
            'ok' => $errors === [],
            'errors' => $errors,
            'root' => $root,
            'version_entry' => $versionEntry,
        ];
    }
}
