<?php

declare(strict_types=1);

require_once CATMIN_CORE . '/module-signature-validator.php';
require_once CATMIN_CORE . '/module-signature-logger.php';

final class CoreModuleSignature
{
    public function verify(string $modulePath, string $moduleSlug = '', ?array $checksums = null): array
    {
        $result = (new CoreModuleSignatureValidator())->validate($modulePath, $checksums);
        (new CoreModuleSignatureLogger())->log(
            $moduleSlug !== '' ? $moduleSlug : basename($modulePath),
            (string) ($result['status'] ?? 'unknown'),
            (string) ($result['key_id'] ?? '')
        );
        return $result;
    }
}

