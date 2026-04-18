<?php

declare(strict_types=1);

require_once CATMIN_CORE . '/module-registry.php';
require_once CATMIN_CORE . '/module-validator.php';
require_once CATMIN_CORE . '/module-compatibility-checker.php';
require_once CATMIN_CORE . '/module-dependency-resolver.php';
require_once CATMIN_CORE . '/module-state-store.php';

final class CoreModuleLoader
{
    private const TYPE_ORDER = [
        'core' => 1,
        'admin' => 2,
        'front' => 3,
        'integration' => 4,
        'integrations' => 4,
        'driver' => 5,
        'drivers' => 5,
    ];

    /** @var array<string, bool> */
    private array $seenModuleIds = [];

    public function scan(): array
    {
        $this->seenModuleIds = [];
        $registry = new CoreModuleRegistry();
        $validator = new CoreModuleValidator();
        $compat = new CoreModuleCompatibilityChecker();
        $stateStore = new CoreModuleStateStore();
        $stateBySlug = $stateStore->stateBySlug();

        foreach (array_keys(self::TYPE_ORDER) as $type) {
            $scopeDir = CATMIN_MODULES . '/' . $type;
            if (!is_dir($scopeDir)) {
                continue;
            }

            foreach (glob($scopeDir . '/*', GLOB_ONLYDIR) ?: [] as $moduleDir) {
                $manifestPath = $moduleDir . '/manifest.json';
                if (!is_file($manifestPath)) {
                    Core\logs\Logger::error('Module détecté sans manifest.json', ['path' => $moduleDir]);
                    continue;
                }

                $raw = file_get_contents($manifestPath);
                $manifest = is_string($raw) ? json_decode($raw, true) : null;
                if (!is_array($manifest)) {
                    Core\logs\Logger::error('Manifest module invalide', ['path' => $manifestPath]);
                    continue;
                }

                $validation = $validator->validate($manifest, $moduleDir);
                $normalized = is_array($validation['normalized'] ?? null) ? $validation['normalized'] : $manifest;
                $compatibility = $compat->check($normalized);
                $slug = strtolower(trim((string) ($normalized['slug'] ?? basename($moduleDir))));

                $moduleId = strtolower(trim((string) ($normalized['module_id'] ?? '')));
                if ($moduleId !== '') {
                    if (isset($this->seenModuleIds[$moduleId])) {
                        Core\logs\Logger::error('Collision critique module_id', ['module_id' => $moduleId, 'path' => $moduleDir]);
                        continue;
                    }
                    $this->seenModuleIds[$moduleId] = true;
                }

                $enabled = (bool) ($normalized['enabled_by_default'] ?? false);
                if (isset($stateBySlug[$slug]['status'])) {
                    $enabled = (string) $stateBySlug[$slug]['status'] === 'active';
                }

                $module = [
                    'path' => $moduleDir,
                    'manifest_path' => $manifestPath,
                    'manifest' => $normalized,
                    'valid' => (bool) ($validation['valid'] ?? false),
                    'compatible' => (bool) ($compatibility['compatible'] ?? false),
                    'enabled' => $enabled,
                    'errors' => array_values(array_merge(
                        (array) ($validation['errors'] ?? []),
                        (array) ($compatibility['errors'] ?? [])
                    )),
                    'state' => 'detected',
                ];

                if (!$module['valid']) {
                    $module['state'] = 'invalid';
                } elseif (!$module['compatible']) {
                    $module['state'] = 'incompatible';
                } elseif (!$enabled) {
                    $module['state'] = 'disabled';
                } else {
                    $module['state'] = 'enabled';
                }

                $registry->add($module);
                $stateStore->persist(
                    $slug,
                    (string) ($normalized['name'] ?? $slug),
                    (string) ($normalized['version'] ?? '0.0.0'),
                    $enabled
                );
                Core\logs\Logger::info('Module détecté', [
                    'slug' => $slug,
                    'type' => (string) ($normalized['type'] ?? $type),
                    'state' => $module['state'],
                ]);
            }
        }

        $modules = $registry->all();
        $resolver = new CoreModuleDependencyResolver();
        $depResult = $resolver->resolve($modules);

        if (!$depResult['ok']) {
            foreach ((array) $depResult['errors'] as $error) {
                Core\logs\Logger::error('Module dependency resolver', ['error' => (string) $error]);
            }
        }

        $ordered = [];
        $bySlug = [];
        foreach ($modules as $module) {
            $mSlug = strtolower(trim((string) ($module['manifest']['slug'] ?? '')));
            if ($mSlug !== '') {
                $bySlug[$mSlug] = $module;
            }
        }

        foreach ((array) $depResult['order'] as $slug) {
            if (!isset($bySlug[$slug])) {
                continue;
            }
            $ordered[] = $bySlug[$slug];
            unset($bySlug[$slug]);
        }
        foreach ($bySlug as $module) {
            $ordered[] = $module;
        }

        usort($ordered, static function (array $a, array $b): int {
            $aType = strtolower((string) ($a['manifest']['type'] ?? 'driver'));
            $bType = strtolower((string) ($b['manifest']['type'] ?? 'driver'));
            $aOrder = self::TYPE_ORDER[$aType] ?? 99;
            $bOrder = self::TYPE_ORDER[$bType] ?? 99;
            if ($aOrder !== $bOrder) {
                return $aOrder <=> $bOrder;
            }
            return strcmp((string) ($a['manifest']['slug'] ?? ''), (string) ($b['manifest']['slug'] ?? ''));
        });

        return [
            'modules' => $ordered,
            'errors' => array_values(array_merge($registry->errors(), (array) ($depResult['errors'] ?? []))),
        ];
    }

    public function loadableForZone(string $zone): array
    {
        $zone = strtolower(trim($zone));
        $snapshot = $this->scan();
        $loadable = [];

        foreach ((array) ($snapshot['modules'] ?? []) as $module) {
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

            $routesMap = is_array($manifest['routes_map'] ?? null) ? $manifest['routes_map'] : [];
            $logicalZones = $this->logicalZonesForRuntimeZone($zone);
            $routeFiles = [];

            foreach ($logicalZones as $logicalZone) {
                $candidateRelative = trim((string) ($routesMap[$logicalZone] ?? ''));
                if ($candidateRelative === '') {
                    continue;
                }
                $candidate = str_starts_with($candidateRelative, '/')
                    ? $candidateRelative
                    : ($module['path'] . '/' . ltrim($candidateRelative, '/'));
                $real = realpath($candidate);
                if (!is_string($real) || $real === '' || !str_starts_with($real, CATMIN_MODULES . '/') || !is_file($real)) {
                    continue;
                }
                $routeFiles[$real] = true;
            }

            if ($routeFiles === []) {
                $routesFile = trim((string) ($manifest['routes'] ?? ''));
                $candidate = $routesFile !== ''
                    ? (str_starts_with($routesFile, '/') ? $routesFile : ($module['path'] . '/' . ltrim($routesFile, '/')))
                    : ($module['path'] . '/routes.php');

                $real = realpath($candidate);
                if (!is_string($real) || $real === '' || !str_starts_with($real, CATMIN_MODULES . '/') || !is_file($real)) {
                    continue;
                }

                $routeFiles[$real] = true;
            }

            foreach (array_keys($routeFiles) as $real) {
                $row = $module;
                $row['routes_file'] = $real;
                $loadable[] = $row;
            }
        }

        return $loadable;
    }

    /** @return array<int, string> */
    private function logicalZonesForRuntimeZone(string $runtimeZone): array
    {
        return match ($runtimeZone) {
            'admin' => ['admin', 'settings'],
            'front' => ['front', 'api', 'ajax', 'tools'],
            default => [$runtimeZone],
        };
    }
}
