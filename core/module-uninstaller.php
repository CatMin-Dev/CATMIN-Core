<?php

declare(strict_types=1);

require_once CATMIN_CORE . '/module-uninstall-validator.php';
require_once CATMIN_CORE . '/module-uninstall-runner.php';
require_once CATMIN_CORE . '/events-bus.php';

final class CoreModuleUninstaller
{
    /** @return array{ok:bool,message:string,impact?:array<string,mixed>,errors?:array<int,string>} */
    public function preview(string $scope, string $slug): array
    {
        $analysis = (new CoreModuleUninstallImpactAnalyzer())->analyze($scope, $slug);
        if (!(bool) ($analysis['ok'] ?? false)) {
            return ['ok' => false, 'message' => 'Analyse impossible', 'errors' => (array) ($analysis['errors'] ?? ['analysis_failed'])];
        }
        return ['ok' => true, 'message' => 'Impact analysé', 'impact' => (array) ($analysis['impact'] ?? [])];
    }

    /** @return array{ok:bool,message:string,errors?:array<int,string>,impact?:array<string,mixed>} */
    public function uninstall(string $scope, string $slug, string $policy): array
    {
        catmin_event_emit('module.uninstall.requested', [
            'scope' => $scope,
            'slug' => $slug,
            'policy' => $policy,
        ]);

        $valid = (new CoreModuleUninstallValidator())->validate($scope, $slug, $policy);
        if (!(bool) ($valid['ok'] ?? false)) {
            catmin_event_emit('module.uninstall.denied', [
                'scope' => $scope,
                'slug' => $slug,
                'policy' => $policy,
                'errors' => (array) ($valid['errors'] ?? []),
            ]);
            return [
                'ok' => false,
                'message' => 'Désinstallation refusée.',
                'errors' => (array) ($valid['errors'] ?? []),
                'impact' => (array) ($valid['impact'] ?? []),
            ];
        }

        $result = (new CoreModuleUninstallRunner())->run((array) ($valid['impact'] ?? []), (string) ($valid['policy'] ?? 'keep_data'));
        catmin_event_emit((bool) ($result['ok'] ?? false) ? 'module.uninstalled' : 'module.uninstall.failed', [
            'scope' => $scope,
            'slug' => $slug,
            'policy' => $policy,
            'result' => $result,
        ]);

        return $result;
    }
}
