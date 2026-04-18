<?php

declare(strict_types=1);

require_once CATMIN_CORE . '/module-public-keyring.php';

final class CoreModuleSignatureValidator
{
    public function validate(string $modulePath, string $signatureRelativePath = '', ?array $checksums = null): array
    {
        $signaturePath = $this->resolveSignaturePath($modulePath, $signatureRelativePath);
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
                'key_status' => 'unknown',
            ];
        }

        $keyScope = strtolower(trim((string) ($entry['scope'] ?? 'community')));
        $keySource = (string) ($entry['source'] ?? 'embedded');
        $keyStatus = strtolower(trim((string) ($entry['status'] ?? 'active')));
        if ($keyScope === 'revoked' || $keyStatus === 'revoked') {
            return [
                'status' => 'revoked_key',
                'valid' => false,
                'errors' => ['Clé révoquée: ' . $keyId],
                'key_id' => $keyId,
                'key_scope' => 'revoked',
                'key_source' => $keySource,
                'key_status' => 'revoked',
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
                    'key_status' => $keyStatus,
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
                'key_status' => $keyStatus,
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
                'key_status' => $keyStatus,
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
                'key_status' => $keyStatus,
            ];
        }

        return [
            'status' => 'signed_valid',
            'valid' => true,
            'errors' => [],
            'key_id' => $keyId,
            'key_scope' => $keyScope,
            'key_source' => $keySource,
            'key_status' => $keyStatus,
        ];
    }

    private function resolveSignaturePath(string $modulePath, string $signatureRelativePath): string
    {
        $moduleRoot = rtrim($modulePath, '/');
        $candidate = trim($signatureRelativePath);
        if ($candidate !== '' && $this->isSafeRelativePath($candidate)) {
            $resolved = $moduleRoot . '/' . ltrim(str_replace('\\', '/', $candidate), '/');
            if (is_file($resolved)) {
                return $resolved;
            }
        }

        return $moduleRoot . '/signature.json';
    }

    private function isSafeRelativePath(string $path): bool
    {
        $path = trim($path);
        if ($path === '' || str_contains($path, "\0")) {
            return false;
        }

        if (str_starts_with($path, '/') || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1) {
            return false;
        }

        foreach (explode('/', str_replace('\\', '/', $path)) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                return false;
            }
        }

        return true;
    }
}

