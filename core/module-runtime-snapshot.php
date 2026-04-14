<?php

declare(strict_types=1);

require_once CATMIN_CORE . '/module-loader.php';

final class CoreModuleRuntimeSnapshot
{
    private ?array $snapshot = null;

    public function all(): array
    {
        if ($this->snapshot === null) {
            $this->snapshot = (new CoreModuleLoader())->scan();
        }

        return $this->snapshot;
    }

    public function refresh(): array
    {
        $this->snapshot = (new CoreModuleLoader())->scan();

        return $this->snapshot;
    }

    public function modules(): array
    {
        return (array) ($this->all()['modules'] ?? []);
    }

    public function findBySlug(string $slug, ?string $scope = null): ?array
    {
        $slug = strtolower(trim($slug));
        $scope = $scope !== null ? strtolower(trim($scope)) : null;

        foreach ($this->modules() as $module) {
            $mSlug = strtolower(trim((string) ($module['manifest']['slug'] ?? '')));
            if ($mSlug !== $slug) {
                continue;
            }
            if ($scope !== null) {
                $mScope = strtolower(trim((string) ($module['manifest']['type'] ?? '')));
                if ($mScope !== $scope) {
                    continue;
                }
            }

            return $module;
        }

        return null;
    }

    public function isEnabled(string $slug, ?string $scope = null): bool
    {
        $module = $this->findBySlug($slug, $scope);
        if (!is_array($module)) {
            return false;
        }

        return (bool) ($module['valid'] ?? false)
            && (bool) ($module['compatible'] ?? false)
            && (bool) ($module['enabled'] ?? false);
    }

    public function loadableForZone(string $zone): array
    {
        $zone = strtolower(trim($zone));
        $loadable = [];

        foreach ($this->modules() as $module) {
            if (!((bool) ($module['valid'] ?? false)) || !((bool) ($module['compatible'] ?? false)) || !((bool) ($module['enabled'] ?? false))) {
                continue;
            }

            $manifest = (array) ($module['manifest'] ?? []);
            $load = is_array($manifest['load'] ?? null) ? $manifest['load'] : [];
            $routesLoad = array_key_exists('routes', $load) ? ((bool) $load['routes']) : true;
            if (!$routesLoad) {
                continue;
            }

            $zones = $manifest['zones'] ?? [$manifest['type'] ?? 'front'];
            if (is_array($zones) && $zones !== []) {
                $zoneMatch = false;
                foreach ($zones as $z) {
                    $z = strtolower(trim((string) $z));
                    if ($z === $zone || ($zone === 'admin' && $z === 'core') || ($zone === 'front' && $z === 'core')) {
                        $zoneMatch = true;
                        break;
                    }
                }
                if (!$zoneMatch) {
                    continue;
                }
            }

            $routesFile = (string) ($manifest['routes'] ?? '');
            if ($routesFile !== '') {
                $candidate = str_starts_with($routesFile, '/') ? $routesFile : ((string) ($module['path'] ?? '') . '/' . ltrim($routesFile, '/'));
            } else {
                $candidate = (string) ($module['path'] ?? '') . '/routes.php';
            }

            $real = realpath($candidate);
            if (!is_string($real) || $real === '' || !str_starts_with($real, CATMIN_MODULES . '/') || !is_file($real)) {
                continue;
            }

            $module['routes_file'] = $real;
            $loadable[] = $module;
        }

        return $loadable;
    }
}