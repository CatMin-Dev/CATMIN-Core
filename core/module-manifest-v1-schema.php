<?php

declare(strict_types=1);

require_once CATMIN_CORE . '/module-ui-anchor-registry.php';

final class CoreModuleManifestV1Schema
{
    /** @var array<int, string> */
    private const ALLOWED_TOP_KEYS = [
        'schema_version',
        'module_id',
        'name',
        'description',
        'version',
        'type',
        'category',
        'authors',
        'compatibility',
        'bootstrap',
        'routes',
        'permissions',
        'settings',
        'navigation',
        'ui',
        'events',
        'notifications',
        'assets',
        'database',
        'dependencies',
        'healthchecks',
        'docs',
        'tests',
        'release',
        'enabled_by_default',
        'zones',
    ];

    /** @return array{valid:bool,errors:array<int,string>} */
    public function validate(array $manifest): array
    {
        $errors = [];

        $schema = trim((string) ($manifest['schema_version'] ?? ''));
        if ($schema === '') {
            return ['valid' => true, 'errors' => []];
        }

        if ($schema !== '1.0') {
            $errors[] = 'schema_version non supportee: ' . $schema;
            return ['valid' => false, 'errors' => $errors];
        }

        foreach (array_keys($manifest) as $key) {
            if (!in_array((string) $key, self::ALLOWED_TOP_KEYS, true)) {
                $errors[] = 'Cle manifest inconnue (V1 strict): ' . (string) $key;
            }
        }

        foreach (['schema_version', 'module_id', 'name', 'version', 'type', 'compatibility', 'bootstrap', 'routes', 'permissions', 'settings', 'dependencies', 'docs', 'release'] as $required) {
            if (!array_key_exists($required, $manifest)) {
                $errors[] = 'Champ manifest V1 obligatoire manquant: ' . $required;
            }
        }

        $moduleId = strtolower(trim((string) ($manifest['module_id'] ?? '')));
        if ($moduleId === '' || preg_match('/^[a-z0-9][a-z0-9-]*$/', str_replace('_', '-', $moduleId)) !== 1) {
            $errors[] = 'module_id invalide';
        }

        if (!is_array($manifest['compatibility'] ?? null)) {
            $errors[] = 'compatibility doit etre un objet';
        }

        if (!is_array($manifest['bootstrap'] ?? null)) {
            $errors[] = 'bootstrap doit etre un objet';
        } else {
            $provider = trim((string) (($manifest['bootstrap']['provider'] ?? '')));
            if (!$this->isSafeRelativePath($provider)) {
                $errors[] = 'bootstrap.provider invalide';
            }
        }

        if (!is_array($manifest['routes'] ?? null)) {
            $errors[] = 'routes doit etre un objet';
        } else {
            foreach ((array) $manifest['routes'] as $zone => $path) {
                $zone = strtolower(trim((string) $zone));
                if (!in_array($zone, ['admin', 'front', 'api', 'ajax', 'settings', 'tools'], true)) {
                    $errors[] = 'routes zone invalide: ' . $zone;
                    continue;
                }
                if (!$this->isSafeRelativePath((string) $path)) {
                    $errors[] = 'routes.' . $zone . ' invalide';
                }
            }
        }

        if (!is_array($manifest['permissions'] ?? null) || !$this->isSafeRelativePath((string) (($manifest['permissions']['file'] ?? '')))) {
            $errors[] = 'permissions.file invalide';
        }

        if (!is_array($manifest['settings'] ?? null) || !$this->isSafeRelativePath((string) (($manifest['settings']['file'] ?? '')))) {
            $errors[] = 'settings.file invalide';
        }

        if (!is_array($manifest['dependencies'] ?? null)) {
            $errors[] = 'dependencies doit etre un objet';
        }

        if (!is_array($manifest['docs'] ?? null) || !$this->isSafeRelativePath((string) (($manifest['docs']['index'] ?? '')))) {
            $errors[] = 'docs.index invalide';
        }

        if (!is_array($manifest['release'] ?? null)) {
            $errors[] = 'release doit etre un objet';
        } else {
            $checksums = trim((string) ($manifest['release']['checksums'] ?? ''));
            $signature = trim((string) ($manifest['release']['signature'] ?? ''));
            $versioning = is_array($manifest['release']['versioning'] ?? null) ? $manifest['release']['versioning'] : null;

            if (!$this->isSafeRelativePath($checksums)) {
                $errors[] = 'release.checksums invalide';
            }
            if (!$this->isSafeRelativePath($signature)) {
                $errors[] = 'release.signature invalide';
            }
            if (!is_array($versioning)) {
                $errors[] = 'release.versioning doit etre un objet';
            } else {
                $strategy = strtolower(trim((string) ($versioning['strategy'] ?? '')));
                if (!in_array($strategy, ['semver', 'calendar', 'custom'], true)) {
                    $errors[] = 'release.versioning.strategy invalide';
                }

                $changelog = trim((string) ($versioning['changelog'] ?? ''));
                if (!$this->isSafeRelativePath($changelog)) {
                    $errors[] = 'release.versioning.changelog invalide';
                }
            }
        }

        if (is_array($manifest['ui']['inject'] ?? null)) {
            $anchorRegistry = new CoreModuleUiAnchorRegistry();
            $seenIds = [];
            foreach ((array) $manifest['ui']['inject'] as $idx => $inject) {
                if (!is_array($inject)) {
                    $errors[] = 'ui.inject[' . $idx . '] doit etre un objet';
                    continue;
                }
                $id = strtolower(trim((string) ($inject['id'] ?? '')));
                $target = strtolower(trim((string) ($inject['target'] ?? '')));
                if ($id === '') {
                    $errors[] = 'ui.inject[' . $idx . '].id manquant';
                } elseif (isset($seenIds[$id])) {
                    $errors[] = 'ui.inject id duplique: ' . $id;
                } else {
                    $seenIds[$id] = true;
                }

                if (!$anchorRegistry->isAllowed($target)) {
                    $errors[] = 'ui.inject[' . $idx . '].target invalide: ' . $target;
                }

                if (isset($inject['view']) && !$this->isSafeRelativePath((string) $inject['view'])) {
                    $errors[] = 'ui.inject[' . $idx . '].view invalide';
                }
                if (isset($inject['file']) && !$this->isSafeRelativePath((string) $inject['file'])) {
                    $errors[] = 'ui.inject[' . $idx . '].file invalide';
                }
            }
        }

        return ['valid' => $errors === [], 'errors' => $errors];
    }

    private function isSafeRelativePath(string $path): bool
    {
        $path = trim($path);
        if ($path === '' || str_starts_with($path, '/') || str_contains($path, '..')) {
            return false;
        }

        return preg_match('/^[A-Za-z0-9._\/-]+$/', $path) === 1;
    }
}
