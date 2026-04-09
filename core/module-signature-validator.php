<?php

declare(strict_types=1);

require_once CATMIN_CORE . '/module-public-keyring.php';

final class CoreModuleSignatureValidator
{
    public function validate(string $modulePath, ?array $checksums = null): array
    {
        $signaturePath = rtrim($modulePath, '/') . '/signature.json';
        if (!is_file($signaturePath)) {
            return [
                'status' => 'unsigned',
                'valid' => false,
                'errors' => ['signature.json manquant'],
                'key_id' => '',
            ];
        }

        $decoded = json_decode((string) file_get_contents($signaturePath), true);
        if (!is_array($decoded)) {
            return [
                'status' => 'signature_invalid',
                'valid' => false,
                'errors' => ['signature.json invalide'],
                'key_id' => '',
            ];
        }

        $keyId = trim((string) ($decoded['key_id'] ?? ''));
        $signature = trim((string) ($decoded['signature'] ?? ''));
        $signedHash = strtolower(trim((string) ($decoded['signed_hash'] ?? '')));
        if ($keyId === '' || $signature === '' || $signedHash === '') {
            return [
                'status' => 'signature_invalid',
                'valid' => false,
                'errors' => ['Champs signature incomplets'],
                'key_id' => $keyId,
            ];
        }

        $keyring = new CoreModulePublicKeyring();
        $entry = $keyring->entry($keyId);
        $publicKey = $keyring->get($keyId);
        if (!is_string($publicKey) || $publicKey === '') {
            return [
                'status' => 'unknown_key',
                'valid' => false,
                'errors' => ['Cle publique inconnue: ' . $keyId],
                'key_id' => $keyId,
                'key_scope' => 'unknown',
                'key_source' => 'unknown',
            ];
        }

        $keyScope = strtolower(trim((string) ($entry['scope'] ?? 'community')));
        $keySource = (string) ($entry['source'] ?? 'embedded');
        if ($keyScope === 'revoked') {
            return [
                'status' => 'revoked_key',
                'valid' => false,
                'errors' => ['Clé révoquée: ' . $keyId],
                'key_id' => $keyId,
                'key_scope' => $keyScope,
                'key_source' => $keySource,
            ];
        }

        if ($checksums !== null) {
            $moduleHash = strtolower(trim((string) ($checksums['module_hash'] ?? '')));
            if ($moduleHash !== '' && !hash_equals($moduleHash, $signedHash)) {
                return [
                'status' => 'signature_invalid',
                'valid' => false,
                'errors' => ['signed_hash != module_hash'],
                'key_id' => $keyId,
                'key_scope' => $keyScope,
                'key_source' => $keySource,
            ];
        }
        }

        $binarySignature = base64_decode($signature, true);
        if (!is_string($binarySignature) || $binarySignature === '') {
            return [
                'status' => 'signature_invalid',
                'valid' => false,
                'errors' => ['Signature base64 invalide'],
                'key_id' => $keyId,
                'key_scope' => $keyScope,
                'key_source' => $keySource,
            ];
        }

        if (!function_exists('openssl_verify')) {
            return [
                'status' => 'signature_invalid',
                'valid' => false,
                'errors' => ['OpenSSL indisponible'],
                'key_id' => $keyId,
                'key_scope' => $keyScope,
                'key_source' => $keySource,
            ];
        }

        $result = @openssl_verify($signedHash, $binarySignature, $publicKey, OPENSSL_ALGO_SHA256);
        if ($result !== 1) {
            return [
                'status' => 'signature_invalid',
                'valid' => false,
                'errors' => ['Verification RSA KO'],
                'key_id' => $keyId,
                'key_scope' => $keyScope,
                'key_source' => $keySource,
            ];
        }

        return [
            'status' => 'signed_valid',
            'valid' => true,
            'errors' => [],
            'key_id' => $keyId,
            'key_scope' => $keyScope,
            'key_source' => $keySource,
        ];
    }
}
