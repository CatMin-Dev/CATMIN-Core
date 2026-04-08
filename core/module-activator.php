<?php

declare(strict_types=1);

require_once CATMIN_CORE . '/module-loader.php';

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
            $loader = new CoreModuleLoader();
            $snapshot = $loader->scan();
            foreach ($snapshot['modules'] as $module) {
                $mSlug = strtolower(trim((string) ($module['manifest']['slug'] ?? '')));
                if ($mSlug !== $slug) {
                    continue;
                }
                $deps = $module['manifest']['dependencies'] ?? [];
                foreach (is_array($deps) ? $deps : [] as $dep) {
                    $depSlug = is_string($dep) ? strtolower(trim($dep)) : strtolower(trim((string) ($dep['slug'] ?? '')));
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
            $loader = new CoreModuleLoader();
            $snapshot = $loader->scan();
            foreach ($snapshot['modules'] as $module) {
                $deps = $module['manifest']['dependencies'] ?? [];
                foreach (is_array($deps) ? $deps : [] as $dep) {
                    $depSlug = is_string($dep) ? strtolower(trim($dep)) : strtolower(trim((string) ($dep['slug'] ?? '')));
                    if ($depSlug === $slug && ((bool) ($module['enabled'] ?? false))) {
                        $modSlug = (string) ($module['manifest']['slug'] ?? '-');
                        return ['ok' => false, 'message' => 'Désactivation refusée, dépendance active: ' . $modSlug];
                    }
                }
            }
        }

        $manifest['enabled'] = $enabled;
        $encoded = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($encoded)) {
            return ['ok' => false, 'message' => 'Erreur encodage manifest'];
        }
        if (@file_put_contents($manifestPath, $encoded . PHP_EOL) === false) {
            return ['ok' => false, 'message' => 'Écriture manifest impossible'];
        }

        Core\logs\Logger::info($enabled ? 'Module activé' : 'Module désactivé', ['scope' => $scope, 'slug' => $slug]);

        return ['ok' => true, 'message' => $enabled ? 'Module activé' : 'Module désactivé'];
    }
}

