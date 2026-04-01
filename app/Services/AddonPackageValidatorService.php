<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use ZipArchive;

class AddonPackageValidatorService
{
    public function __construct(private readonly AddonCompatibilityService $compatibilityService)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function validateArchive(string $archivePath, ?string $expectedChecksum = null): array
    {
        if (!File::exists($archivePath)) {
            return ['ok' => false, 'message' => 'Archive addon introuvable.'];
        }

        if (!str_ends_with(strtolower($archivePath), '.zip')) {
            return ['ok' => false, 'message' => 'Format archive invalide: zip requis.'];
        }

        if (!class_exists(ZipArchive::class)) {
            return ['ok' => false, 'message' => 'Extension PHP zip absente.'];
        }

        $actualChecksum = hash_file('sha256', $archivePath) ?: '';
        if ($expectedChecksum !== null && $expectedChecksum !== '' && !hash_equals(strtolower($expectedChecksum), strtolower($actualChecksum))) {
            return ['ok' => false, 'message' => 'Checksum package invalide.', 'checksum' => $actualChecksum];
        }

        $zip = new ZipArchive();
        if ($zip->open($archivePath) !== true) {
            return ['ok' => false, 'message' => 'Archive zip illisible.'];
        }

        $entries = [];
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $entries[] = (string) $zip->getNameIndex($index);
        }

        $manifestPath = $this->findManifestPath($entries);
        if ($manifestPath === null) {
            $zip->close();
            return ['ok' => false, 'message' => 'addon.json manquant dans l archive.'];
        }

        $manifestRaw = $zip->getFromName($manifestPath);
        $zip->close();

        if (!is_string($manifestRaw) || trim($manifestRaw) === '') {
            return ['ok' => false, 'message' => 'addon.json vide ou illisible.'];
        }

        $manifest = AddonManifestService::decodeJson($manifestRaw);
        if ($manifest === null || !AddonManifestService::isManifestValid($manifest)) {
            return ['ok' => false, 'message' => 'Manifest addon invalide.'];
        }

        $structure = $this->validateStructure($entries, $manifestPath, $manifest);
        if (($structure['ok'] ?? false) !== true) {
            return [
                'ok' => false,
                'message' => 'Structure archive invalide: ' . implode(', ', (array) ($structure['missing'] ?? [])),
                'manifest' => $manifest,
                'checksum' => $actualChecksum,
            ];
        }

        $compatibility = $this->compatibilityService->evaluate($manifest);

        return [
            'ok' => ($compatibility['compatible'] ?? false) === true,
            'message' => (string) ($compatibility['summary'] ?? 'Validation terminee.'),
            'manifest' => $manifest,
            'checksum' => $actualChecksum,
            'structure' => $structure,
            'compatibility' => $compatibility,
        ];
    }

    /**
     * @param array<int, string> $entries
     * @return array<string, mixed>
     */
    public function inspectEntries(array $entries, string $archivePath = ''): array
    {
        $manifestPath = $this->findManifestPath($entries);
        return [
            'ok' => $manifestPath !== null,
            'manifest_path' => $manifestPath,
            'entries' => $entries,
            'archive_path' => $archivePath,
        ];
    }

    /**
     * @param array<int, string> $entries
     * @return array<string, mixed>
     */
    private function validateStructure(array $entries, string $manifestPath, array $manifest): array
    {
        $basePath = str_replace('addon.json', '', $manifestPath);
        $required = [];

        if ((bool) ($manifest['has_routes'] ?? false)) {
            $required[] = $basePath . 'routes.php';
        }
        if ((bool) ($manifest['has_migrations'] ?? false)) {
            $required[] = $basePath . 'Migrations/';
        }
        if ((bool) ($manifest['has_assets'] ?? false)) {
            $required[] = $basePath . 'Assets/';
        }
        if ((bool) ($manifest['has_views'] ?? false)) {
            $required[] = $basePath . 'Views/';
        }

        $missing = [];
        foreach ($required as $expected) {
            $found = false;
            foreach ($entries as $entry) {
                if ($entry === $expected || str_starts_with($entry, $expected)) {
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $missing[] = $expected;
            }
        }

        return [
            'ok' => $missing === [],
            'missing' => $missing,
        ];
    }

    /**
     * @param array<int, string> $entries
     */
    private function findManifestPath(array $entries): ?string
    {
        foreach ($entries as $entry) {
            if (str_ends_with($entry, 'addon.json')) {
                return $entry;
            }
        }

        return null;
    }
}
