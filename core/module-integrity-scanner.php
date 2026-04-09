<?php

declare(strict_types=1);

require_once CATMIN_CORE . '/module-loader.php';
require_once CATMIN_CORE . '/module-integrity.php';
require_once CATMIN_CORE . '/module-integrity-logger.php';
require_once CATMIN_CORE . '/module-integrity-reporter.php';

final class CoreModuleIntegrityScanner
{
    public function scanAll(bool $persistReport = true): array
    {
        $loader = new CoreModuleLoader();
        $snapshot = $loader->scan();
        $rows = [];
        $summary = [
            'valid' => 0,
            'warning' => 0,
            'invalid' => 0,
            'tampered' => 0,
            'unsigned' => 0,
            'unknown_key' => 0,
            'revoked_key' => 0,
        ];

        foreach ((array) ($snapshot['modules'] ?? []) as $module) {
            $manifest = (array) ($module['manifest'] ?? []);
            $slug = strtolower(trim((string) ($manifest['slug'] ?? '')));
            $path = (string) ($module['path'] ?? '');
            if ($slug === '' || $path === '' || !is_dir($path)) {
                continue;
            }

            $state = (new CoreModuleIntegrity())->verify($path, $manifest);
            $integrityStatus = (string) ($state['integrity']['status'] ?? 'invalid');
            $signatureStatus = (string) ($state['signature']['status'] ?? 'unsigned');
            $keyScope = (string) ($state['signature']['key_scope'] ?? 'unknown');
            $keyStatus = (string) ($state['signature']['key_status'] ?? 'unknown');
            $trusted = (bool) ($state['trust']['trusted'] ?? false);

            if (isset($summary[$integrityStatus])) {
                $summary[$integrityStatus]++;
            } elseif ($integrityStatus === 'missing_checksums' || $integrityStatus === 'unsupported_schema') {
                $summary['warning']++;
            } else {
                $summary['invalid']++;
            }
            if (isset($summary[$signatureStatus])) {
                $summary[$signatureStatus]++;
            }

            (new CoreModuleIntegrityLogger())->log($slug, $integrityStatus, [
                'signature' => $signatureStatus,
                'trusted' => $trusted,
            ]);

            $rows[] = [
                'slug' => $slug,
                'type' => (string) ($manifest['type'] ?? ''),
                'version' => (string) ($manifest['version'] ?? ''),
                'integrity_status' => $integrityStatus,
                'signature_status' => $signatureStatus,
                'trusted' => $trusted,
                'key_scope' => $keyScope,
                'key_status' => $keyStatus,
                'state' => $state,
            ];
        }

        $report = [
            'generated_at' => gmdate('c'),
            'summary' => $summary,
            'modules' => $rows,
        ];

        if ($persistReport) {
            (new CoreModuleIntegrityReporter())->write($report);
        }
        return $report;
    }
}
