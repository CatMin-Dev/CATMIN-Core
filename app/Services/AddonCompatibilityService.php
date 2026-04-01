<?php

namespace App\Services;

class AddonCompatibilityService
{
    /**
     * @param array<string, mixed> $manifest
     * @return array<string, mixed>
     */
    public function evaluate(array $manifest): array
    {
        $warnings = [];
        $blockers = [];

        $currentCoreRaw = (string) config('app.dashboard_version', 'V3-dev');
        $currentCore = AddonManifestService::normalizeCoreVersion($currentCoreRaw);
        $requiredCore = AddonManifestService::normalizeCoreVersion((string) ($manifest['required_core_version'] ?? ''));

        if ($requiredCore !== '0.0.0' && version_compare($currentCore, $requiredCore, '<')) {
            $blockers[] = sprintf('CATMIN %s requis, version actuelle %s.', $requiredCore, $currentCoreRaw);
        }

        $requiredPhp = trim((string) ($manifest['required_php_version'] ?? '8.2.0'));
        if ($requiredPhp !== '' && version_compare(PHP_VERSION, $requiredPhp, '<')) {
            $blockers[] = sprintf('PHP %s requis, version actuelle %s.', $requiredPhp, PHP_VERSION);
        }

        foreach ((array) ($manifest['required_modules'] ?? []) as $moduleSlug) {
            $moduleSlug = trim((string) $moduleSlug);
            if ($moduleSlug === '') {
                continue;
            }

            if (!ModuleManager::exists($moduleSlug) || !ModuleManager::isDeclaredEnabled($moduleSlug)) {
                $blockers[] = 'Module requis manquant ou desactive: ' . $moduleSlug . '.';
            }
        }

        foreach ((array) ($manifest['dependencies'] ?? []) as $addonSlug) {
            $addonSlug = trim((string) $addonSlug);
            if ($addonSlug === '') {
                continue;
            }

            if (!AddonManager::exists($addonSlug)) {
                $blockers[] = 'Addon requis manquant: ' . $addonSlug . '.';
                continue;
            }

            $dependency = AddonManager::find($addonSlug);
            if ($dependency !== null && !((bool) ($dependency->enabled ?? false))) {
                $warnings[] = 'Addon dependance present mais non active: ' . $addonSlug . '.';
            }
        }

        if ((bool) ($manifest['has_events'] ?? false) && !class_exists(CatminEventBus::class)) {
            $warnings[] = 'Support events indisponible dans cet environnement.';
        }

        $status = 'compatible';
        if ($blockers !== []) {
            $status = 'incompatible';
        } elseif ($warnings !== []) {
            $status = 'compatible_with_warnings';
        }

        return [
            'compatible' => $blockers === [],
            'status' => $status,
            'warnings' => $warnings,
            'blockers' => $blockers,
            'summary' => $blockers !== []
                ? 'Incompatible bloquant'
                : ($warnings !== [] ? 'Compatible avec warning' : 'Compatible'),
            'environment' => [
                'core_version' => $currentCoreRaw,
                'php_version' => PHP_VERSION,
            ],
        ];
    }
}
