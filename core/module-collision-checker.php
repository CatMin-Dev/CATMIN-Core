<?php

declare(strict_types=1);

require_once CATMIN_CORE . '/module-loader.php';

final class CoreModuleCollisionChecker
{
    public function check(array $manifest): array
    {
        $errors = [];
        $slug = strtolower(trim((string) ($manifest['slug'] ?? '')));
        $type = strtolower(trim((string) ($manifest['type'] ?? '')));
        if ($slug === '' || $type === '') {
            return ['ok' => false, 'errors' => ['slug/type invalides']];
        }

        $targetPath = CATMIN_MODULES . '/' . $type . '/' . $slug;
        if (is_dir($targetPath)) {
            $errors[] = 'Slug deja installe: ' . $slug;
        }

        $snapshot = (new CoreModuleLoader())->scan();
        $installed = (array) ($snapshot['modules'] ?? []);
        $installedSlugs = [];
        foreach ($installed as $module) {
            $installedSlugs[] = strtolower(trim((string) ($module['manifest']['slug'] ?? '')));
        }

        $conflicts = (array) ($manifest['dependencies']['conflicts'] ?? []);
        foreach ($conflicts as $conflictSlug) {
            $conflictSlug = strtolower(trim((string) $conflictSlug));
            if ($conflictSlug !== '' && in_array($conflictSlug, $installedSlugs, true)) {
                $errors[] = 'Conflit detecte avec module installe: ' . $conflictSlug;
            }
        }

        return ['ok' => $errors === [], 'errors' => $errors];
    }
}

