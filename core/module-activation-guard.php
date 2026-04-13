<?php

declare(strict_types=1);

require_once CATMIN_CORE . '/module-loader.php';
require_once CATMIN_CORE . '/module-compatibility-checker.php';
require_once CATMIN_CORE . '/module-integrity.php';
require_once CATMIN_CORE . '/module-capability-policy.php';
require_once CATMIN_CORE . '/module-mandatory-dependencies.php';

final class CoreModuleActivationGuard
{
    public function assertCanActivate(string $modulePath, array $manifest, ?string $repoTrust = null): array
    {
        $errors = [];
        $compat = (new CoreModuleCompatibilityChecker())->check($manifest);
        if (!(bool) ($compat['compatible'] ?? false)) {
            $errors = array_merge($errors, (array) ($compat['errors'] ?? []));
        }

        $integrity = (new CoreModuleIntegrity())->verify($modulePath, $manifest);
        if (!((bool) ($integrity['trust']['trusted'] ?? false))) {
            $errors = array_merge($errors, (array) ($integrity['trust']['errors'] ?? []));
        }

        $capabilities = (new CoreModuleCapabilityPolicy())->evaluate($manifest, $repoTrust);
        if (!(bool) ($capabilities['ok'] ?? false)) {
            $errors = array_merge($errors, (array) ($capabilities['errors'] ?? []));
        }

        $snapshot = (new CoreModuleLoader())->scan();
        $installedBySlug = [];
        foreach ((array) ($snapshot['modules'] ?? []) as $module) {
            $mSlug = strtolower(trim((string) ($module['manifest']['slug'] ?? '')));
            if ($mSlug !== '') {
                $installedBySlug[$mSlug] = $module;
            }
        }

        foreach (CoreModuleMandatoryDependencies::mergedWithManifest($manifest) as $required) {
            $required = strtolower(trim((string) $required));
            if ($required === '') {
                continue;
            }
            $dep = $installedBySlug[$required] ?? null;
            if (!is_array($dep) || !((bool) ($dep['enabled'] ?? false))) {
                $errors[] = 'Dependance requise inactive: ' . $required;
            }
        }

        return [
            'ok' => $errors === [],
            'errors' => array_values(array_unique($errors)),
            'integrity' => $integrity,
            'capabilities' => $capabilities,
            'compatibility_state' => (string) ($compat['state'] ?? 'unknown'),
        ];
    }
}
