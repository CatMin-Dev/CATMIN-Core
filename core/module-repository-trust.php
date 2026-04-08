<?php

declare(strict_types=1);

final class CoreModuleRepositoryTrust
{
    /**
     * @param array<string,mixed> $repository
     * @param array<string,mixed> $manifest
     * @param array<string,mixed> $policy
     * @return array{install_allowed:bool,visible:bool,warnings:array<int,string>,level:string}
     */
    public function evaluate(array $repository, array $manifest, array $policy): array
    {
        $level = strtolower(trim((string) ($repository['trust_level'] ?? 'community')));
        $warnings = [];

        $allowByLevel = [
            'official' => (bool) ($policy['allow_official'] ?? true),
            'trusted' => (bool) ($policy['allow_trusted'] ?? true),
            'community' => (bool) ($policy['allow_community'] ?? false),
            'blocked' => false,
        ];

        $installAllowed = (bool) ($allowByLevel[$level] ?? false);

        if ($level === 'blocked') {
            $warnings[] = 'Dépôt bloqué.';
        }

        if ((bool) ($repository['requires_manifest_standard'] ?? true)) {
            $required = ['slug', 'name', 'version'];
            foreach ($required as $field) {
                if (trim((string) ($manifest[$field] ?? '')) === '') {
                    $warnings[] = 'Manifest incomplet: ' . $field;
                    $installAllowed = false;
                }
            }
        }

        $requireChecksums = (bool) ($repository['requires_checksums'] ?? false)
            || (bool) ($policy['require_checksums_' . $level] ?? false);
        if ($requireChecksums) {
            $checksums = $manifest['checksums'] ?? null;
            if (!is_array($checksums) || $checksums === []) {
                $warnings[] = 'Checksums requis mais absents.';
                $installAllowed = false;
            }
        }

        $requireSignature = (bool) ($repository['requires_signature'] ?? false)
            || (bool) ($policy['require_signature_' . $level] ?? false);
        if ($requireSignature) {
            $hasSignature = isset($manifest['signature']) || isset($manifest['signatures']);
            if (!$hasSignature) {
                $warnings[] = 'Signature requise mais absente.';
                $installAllowed = false;
            }
        }

        $visible = true;
        if ((bool) ($policy['hide_unverified_modules'] ?? false) && !$installAllowed) {
            $visible = false;
        }

        return [
            'install_allowed' => $installAllowed,
            'visible' => $visible,
            'warnings' => $warnings,
            'level' => $level,
        ];
    }

    public function scoreLevel(string $level): int
    {
        return match (strtolower(trim($level))) {
            'official' => 4,
            'trusted' => 3,
            'community' => 2,
            'blocked' => 0,
            default => 1,
        };
    }
}
