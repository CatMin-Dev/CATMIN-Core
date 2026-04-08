<?php

declare(strict_types=1);

require_once CATMIN_CORE . '/module-manifest-standard.php';

final class CoreModuleValidator
{
    public function validate(array $manifest, string $modulePath): array
    {
        $standard = new CoreModuleManifestStandard();
        $normalized = $standard->normalize($manifest);
        $validation = $standard->validate($normalized);
        $errors = (array) ($validation['errors'] ?? []);

        foreach (['routes.php', 'hooks.php', 'permissions.php', 'settings.php'] as $file) {
            $path = $modulePath . '/' . $file;
            $real = realpath($path);
            if (is_file($path) && (!is_string($real) || !str_starts_with($real, $modulePath . '/'))) {
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
