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
        $integrityStatus = (string) ($integrityState['status'] ?? 'missing_checksums');

        $trusted = true;
        $warnings = [];
        $errors = [];

        if (!in_array($integrityStatus, ['valid'], true)) {
            if (in_array($integrityStatus, ['tampered', 'invalid'], true)) {
                $trusted = false;
                $errors[] = 'Integrite module invalide';
            } else {
                $warnings[] = 'Checksums absents ou non valides';
            }
        }

        if ($signatureStatus === 'signed_valid') {
            // trusted by signature
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
            'warnings' => $warnings,
            'errors' => $errors,
        ];
    }
}

