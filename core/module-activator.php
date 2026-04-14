<?php

declare(strict_types=1);

require_once CATMIN_CORE . '/module-runtime-snapshot.php';
require_once CATMIN_CORE . '/module-activation-guard.php';
require_once CATMIN_CORE . '/module-state-store.php';
require_once CATMIN_CORE . '/module-mandatory-dependencies.php';

final class CoreModuleActivator
{
    public function activate(string $scope, string $slug): array
    {
        return $this->toggle($scope, $slug, true);
    }

    public function deactivate(string $scope, string $slug): array
    {
        return $this->toggle($scope, $slug, false);
    }

    private function toggle(string $scope, string $slug, bool $enabled): array
    {
        $scope = strtolower(trim($scope));
        $slug = strtolower(trim($slug));
        if ($scope === '' || $slug === '') {
            return ['ok' => false, 'message' => 'Paramètres module invalides'];
        }

        $path = CATMIN_MODULES . '/' . $scope . '/' . $slug;
        $manifestPath = is_file($path . '/manifest.json') ? ($path . '/manifest.json') : ($path . '/module.json');
        if (!is_file($manifestPath)) {
            return ['ok' => false, 'message' => 'Manifest introuvable'];
        }

        $raw = file_get_contents($manifestPath);
        $manifest = is_string($raw) ? json_decode($raw, true) : null;
        if (!is_array($manifest)) {
            return ['ok' => false, 'message' => 'Manifest invalide'];
        }

        if ($enabled) {
            $guard = (new CoreModuleActivationGuard())->assertCanActivate($path, $manifest);
            if (!(bool) ($guard['ok'] ?? false)) {
                return ['ok' => false, 'message' => implode(' | ', (array) ($guard['errors'] ?? ['Activation bloquee']))];
            }

            $runtimeSnapshot = new CoreModuleRuntimeSnapshot();
            $snapshot = $runtimeSnapshot->all();
            foreach ($snapshot['modules'] as $module) {
                $mSlug = strtolower(trim((string) ($module['manifest']['slug'] ?? '')));
                if ($mSlug !== $slug) {
                    continue;
                }
                foreach ($this->extractRequires((array) ($module['manifest'] ?? [])) as $depSlug) {
                    if ($depSlug === '') {
                        continue;
                    }
                    $depModule = null;
                    foreach ($snapshot['modules'] as $m2) {
                        if (strtolower(trim((string) ($m2['manifest']['slug'] ?? ''))) === $depSlug) {
                            $depModule = $m2;
                            break;
                        }
                    }
                    if (!is_array($depModule) || !((bool) ($depModule['enabled'] ?? false))) {
                        return ['ok' => false, 'message' => 'Dépendance active bloquante: ' . $depSlug];
                    }
                }
            }
        } else {
            $runtimeSnapshot = new CoreModuleRuntimeSnapshot();
            $snapshot = $runtimeSnapshot->all();
            foreach ($snapshot['modules'] as $module) {
                $requiredDeps = $this->extractRequires((array) ($module['manifest'] ?? []));
                $requiredDeps = array_merge(
                    $requiredDeps,
                    CoreModuleMandatoryDependencies::forSlug((string) ($module['manifest']['slug'] ?? ''))
                );
                $requiredDeps = array_values(array_unique($requiredDeps));

                foreach ($requiredDeps as $depSlug) {
                    if ($depSlug === $slug && ((bool) ($module['enabled'] ?? false))) {
                        $modSlug = (string) ($module['manifest']['slug'] ?? '-');
                        return ['ok' => false, 'message' => 'Désactivation refusée, dépendance active: ' . $modSlug];
                    }
                }
            }
        }

        // Persist runtime state only; never mutate manifest.json (checksums integrity).
        (new CoreModuleStateStore())->persist(
            $slug,
            (string) ($manifest['name'] ?? $slug),
            (string) ($manifest['version'] ?? '0.0.0'),
            $enabled
        );

        Core\logs\Logger::info($enabled ? 'Module activé' : 'Module désactivé', ['scope' => $scope, 'slug' => $slug]);

        return ['ok' => true, 'message' => $enabled ? 'Module activé' : 'Module désactivé'];
    }

    private function extractRequires(array $manifest): array
    {
        $deps = $manifest['dependencies'] ?? [];
        if (!is_array($deps)) {
            return [];
        }
        if (array_is_list($deps)) {
            return array_values(array_unique(array_filter(array_map(static fn ($dep): string => strtolower(trim((string) $dep)), $deps), static fn (string $v): bool => $v !== '')));
        }
        return array_values(array_unique(array_filter(array_map(static fn ($dep): string => strtolower(trim((string) $dep)), (array) ($deps['requires'] ?? [])), static fn (string $v): bool => $v !== '')));
    }
}
