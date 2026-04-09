<?php

declare(strict_types=1);

final class CoreModuleTrustPolicy
{
    public function mode(): string
    {
        $mode = strtolower(trim((string) config('module-trust.mode', 'recommended')));
        return in_array($mode, ['permissive', 'recommended', 'strict'], true) ? $mode : 'recommended';
    }

    public function evaluate(array $manifest, array $signatureState, array $integrityState): array
    {
        $mode = $this->mode();
        $author = strtolower(trim((string) ($manifest['author'] ?? '')));
        $officialPublishers = array_map(static fn ($v): string => strtolower(trim((string) $v)), (array) config('module-trust.official_publishers', []));
        $isOfficial = $author !== '' && in_array($author, $officialPublishers, true);

        $signatureStatus = (string) ($signatureState['status'] ?? 'unsigned');
        $keyScope = strtolower(trim((string) ($signatureState['key_scope'] ?? 'unknown')));
        $keyStatus = strtolower(trim((string) ($signatureState['key_status'] ?? 'active')));
        $integrityStatus = (string) ($integrityState['status'] ?? 'missing_checksums');

        $trusted = true;
        $warnings = [];
        $errors = [];

        if (!in_array($integrityStatus, ['valid'], true)) {
            if ($mode === 'strict') {
                $trusted = false;
                $errors[] = 'Checksums requis: intégrité non valide';
            } elseif (in_array($integrityStatus, ['tampered', 'invalid'], true)) {
                $trusted = false;
                $errors[] = 'Integrite module invalide';
            } else {
                $warnings[] = 'Checksums absents ou non valides';
            }
        }

        if ($signatureStatus === 'revoked_key') {
            $trusted = false;
            $errors[] = 'Signature basée sur une clé révoquée';
        } elseif ($signatureStatus === 'signed_valid') {
            if ($keyScope === 'revoked' || $keyStatus === 'revoked') {
                $trusted = false;
                $errors[] = 'Signature basée sur une clé révoquée';
            } elseif ($keyScope === 'local_only') {
                $warnings[] = 'Signature locale (local_only) détectée';
                if ((bool) config('trust-policy.allow_local_only_modules', false) === false) {
                    $trusted = false;
                    $errors[] = 'Clé local_only refusée par policy';
                }
            } elseif ($keyStatus === 'deprecated') {
                $warnings[] = 'Clé de signature dépréciée';
            }
        } elseif ($mode === 'strict' || ($mode === 'recommended' && $isOfficial)) {
            $trusted = false;
            $errors[] = 'Signature requise par la policy';
        } else {
            $warnings[] = 'Module non signe';
        }

        return [
            'trusted' => $trusted,
            'mode' => $mode,
            'is_official' => $isOfficial,
            'key_scope' => $keyScope,
            'key_status' => $keyStatus,
            'warnings' => $warnings,
            'errors' => $errors,
        ];
    }
}
