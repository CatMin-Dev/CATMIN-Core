<?php

declare(strict_types=1);

require_once CATMIN_CORE . '/module-checksum-validator.php';
require_once CATMIN_CORE . '/module-signature-validator.php';
require_once CATMIN_CORE . '/module-trust-policy.php';

final class CoreModuleIntegrity
{
    public function verify(string $modulePath, array $manifest): array
    {
        $release = is_array($manifest['release'] ?? null) ? $manifest['release'] : [];
        $checksumState = (new CoreModuleChecksumValidator())->validate(
            $modulePath,
            (string) ($release['checksums'] ?? '')
        );
        $checksums = is_array($checksumState['checksums'] ?? null) ? $checksumState['checksums'] : null;
        $signatureState = (new CoreModuleSignatureValidator())->validate(
            $modulePath,
            (string) ($release['signature'] ?? ''),
            $checksums
        );
        $trust = (new CoreModuleTrustPolicy())->evaluate($manifest, $signatureState, $checksumState);

        return [
            'verified_at' => gmdate('c'),
            'integrity' => $checksumState,
            'signature' => $signatureState,
            'trust' => $trust,
        ];
    }
}
