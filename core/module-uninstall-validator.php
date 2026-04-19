<?php

declare(strict_types=1);

require_once CATMIN_CORE . '/module-uninstall-impact-analyzer.php';
require_once CATMIN_CORE . '/module-data-retention-policy.php';

final class CoreModuleUninstallValidator
{
    /** @return array{ok:bool,impact:array<string,mixed>,policy:string,errors:array<int,string>} */
    public function validate(string $scope, string $slug, string $policy, bool $destructiveConfirmed = false): array
    {
        $analysis = (new CoreModuleUninstallImpactAnalyzer())->analyze($scope, $slug);
        if (!(bool) ($analysis['ok'] ?? false)) {
            return ['ok' => false, 'impact' => [], 'policy' => 'drop_data', 'errors' => (array) ($analysis['errors'] ?? ['analysis_failed'])];
        }

        $impact = (array) ($analysis['impact'] ?? []);
        $retentionPolicy = new CoreModuleDataRetentionPolicy();
        $normalizedPolicy = $retentionPolicy->normalize($policy);
        $errors = [];

        if ((bool) ($impact['non_uninstallable'] ?? false)) {
            $errors[] = 'module_non_uninstallable';
        }
        if ((array) ($impact['active_reverse_dependencies'] ?? []) !== []) {
            $errors[] = 'module_has_active_reverse_dependencies';
        }
        if ($retentionPolicy->isDestructive($normalizedPolicy) && !$destructiveConfirmed) {
            $errors[] = 'destructive_confirmation_required';
        }

        return [
            'ok' => $errors === [],
            'impact' => $impact,
            'policy' => $normalizedPolicy,
            'errors' => $errors,
        ];
    }
}

