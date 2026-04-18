<?php

declare(strict_types=1);

require_once CATMIN_CORE . '/module-manifest-standard.php';
require_once CATMIN_CORE . '/module-manifest-v1-schema.php';

final class CoreModuleValidator
{
    public function validate(array $manifest, string $modulePath): array
    {
        $v1Schema = new CoreModuleManifestV1Schema();
        $schemaState = $v1Schema->validate($manifest);

        $standard = new CoreModuleManifestStandard();
        $normalized = $standard->normalize($manifest);
        $validation = $standard->validate($normalized);
        $errors = array_values(array_merge(
            (array) ($schemaState['errors'] ?? []),
            (array) ($validation['errors'] ?? [])
        ));

        $declaredFiles = [
            (string) ($normalized['bootstrap']['provider'] ?? 'module.php'),
            (string) ($normalized['permissions']['file'] ?? 'permissions.php'),
            (string) ($normalized['settings']['file'] ?? 'settings.php'),
            (string) ($normalized['docs']['index'] ?? ''),
            (string) ($normalized['healthchecks']['provider'] ?? ''),
            (string) ($normalized['notifications']['provider'] ?? ''),
            (string) ($normalized['release']['checksums'] ?? ''),
            (string) ($normalized['release']['signature'] ?? ''),
            (string) ($normalized['release']['versioning']['changelog'] ?? ''),
        ];

        $routeMap = is_array($normalized['routes_map'] ?? null) ? $normalized['routes_map'] : [];
        foreach ($routeMap as $routeFile) {
            $declaredFiles[] = (string) $routeFile;
        }

        foreach ((array) ($normalized['assets']['admin']['css'] ?? []) as $asset) {
            $declaredFiles[] = (string) $asset;
        }
        foreach ((array) ($normalized['assets']['admin']['js'] ?? []) as $asset) {
            $declaredFiles[] = (string) $asset;
        }
        foreach ((array) ($normalized['assets']['front']['css'] ?? []) as $asset) {
            $declaredFiles[] = (string) $asset;
        }
        foreach ((array) ($normalized['assets']['front']['js'] ?? []) as $asset) {
            $declaredFiles[] = (string) $asset;
        }

        foreach ((array) ($normalized['ui']['inject'] ?? []) as $inject) {
            if (!is_array($inject)) {
                continue;
            }
            $declaredFiles[] = (string) ($inject['view'] ?? '');
            $declaredFiles[] = (string) ($inject['file'] ?? '');
        }

        foreach (array_values(array_unique(array_filter(array_map(
            static fn ($v): string => trim((string) $v),
            $declaredFiles
        ), static fn (string $v): bool => $v !== ''))) as $file) {
            $path = $modulePath . '/' . ltrim($file, '/');
            $real = realpath($path);
            if (
                (is_file($path) || is_dir($path))
                && (!is_string($real) || !str_starts_with($real, rtrim($modulePath, '/') . '/'))
            ) {
                $errors[] = 'Chemin dangereux détecté: ' . $file;
            }
        }

        return [
            'valid' => $errors === [],
            'errors' => $errors,
            'normalized' => $normalized,
        ];
    }
}
