<?php

declare(strict_types=1);

require_once CATMIN_CORE . '/module-loader.php';

final class CoreModuleUninstallImpactAnalyzer
{
    /** @return array{ok:bool,impact:array<string,mixed>,errors:array<int,string>} */
    public function analyze(string $scope, string $slug): array
    {
        $scope = strtolower(trim($scope));
        $slug = strtolower(trim($slug));
        if ($scope === '' || $slug === '') {
            return ['ok' => false, 'impact' => [], 'errors' => ['module_invalid']];
        }

        $scan = (new CoreModuleLoader())->scan();
        $target = null;
        $reverseDependencies = [];
        foreach ((array) ($scan['modules'] ?? []) as $module) {
            $manifest = is_array($module['manifest'] ?? null) ? $module['manifest'] : [];
            $mSlug = strtolower(trim((string) ($manifest['slug'] ?? '')));
            $mScope = strtolower(trim((string) ($module['scope'] ?? ($manifest['type'] ?? ''))));
            if ($mSlug === $slug && $mScope === $scope) {
                $target = $module;
            }

            $deps = $manifest['dependencies'] ?? [];
            $requires = [];
            if (is_array($deps)) {
                $requires = array_is_list($deps) ? $deps : ((array) ($deps['requires'] ?? []));
            }
            foreach ($requires as $dep) {
                if (strtolower(trim((string) $dep)) === $slug) {
                    $reverseDependencies[] = [
                        'slug' => $mSlug,
                        'name' => (string) ($manifest['name'] ?? $mSlug),
                        'enabled' => (bool) ($module['enabled'] ?? false),
                    ];
                }
            }
        }

        if (!is_array($target)) {
            return ['ok' => false, 'impact' => [], 'errors' => ['module_not_found']];
        }

        $manifest = is_array($target['manifest'] ?? null) ? $target['manifest'] : [];
        $nonUninstallable = $scope === 'core' || ((bool) ($manifest['non_uninstallable'] ?? false));

        return [
            'ok' => true,
            'impact' => [
                'scope' => $scope,
                'slug' => $slug,
                'name' => (string) ($manifest['name'] ?? $slug),
                'version' => (string) ($manifest['version'] ?? '0.0.0'),
                'enabled' => (bool) ($target['enabled'] ?? false),
                'non_uninstallable' => $nonUninstallable,
                'reverse_dependencies' => $reverseDependencies,
                'active_reverse_dependencies' => array_values(array_filter($reverseDependencies, static fn (array $row): bool => (bool) ($row['enabled'] ?? false))),
                'module_path' => CATMIN_MODULES . '/' . $scope . '/' . $slug,
            ],
            'errors' => [],
        ];
    }
}

