#!/usr/bin/env php
<?php

declare(strict_types=1);

if ($argc < 2) {
    fwrite(STDERR, "Usage: php verify-module-release.php <module.zip> [--require-signature] [--public-key=/path/public.pem]\n");
    exit(1);
}

$zipPath = $argv[1];
$requireSignature = in_array('--require-signature', $argv, true);
$publicKeyPath = '';
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--public-key=')) {
        $publicKeyPath = substr($arg, strlen('--public-key='));
        break;
    }
}

if (!is_file($zipPath)) {
    fwrite(STDERR, "Module ZIP not found: {$zipPath}\n");
    exit(1);
}

$rootDir = realpath(__DIR__ . '/../../');
if (!is_string($rootDir) || $rootDir === '') {
    fwrite(STDERR, "Unable to resolve project root\n");
    exit(1);
}

require_once $rootDir . '/bootstrap.php';
require_once CATMIN_CORE . '/module-zip-validator.php';
require_once CATMIN_CORE . '/module-checksum-validator.php';
require_once CATMIN_CORE . '/module-signature-validator.php';

$zipValidation = (new CoreModuleZipValidator())->validateArchive($zipPath);
if (!(bool) ($zipValidation['ok'] ?? false)) {
    fwrite(STDERR, "ZIP validation failed: " . implode(' | ', (array) ($zipValidation['errors'] ?? [])) . "\n");
    exit(1);
}

$tmp = sys_get_temp_dir() . '/catmin-module-verify-' . bin2hex(random_bytes(6));
if (!@mkdir($tmp, 0775, true) && !is_dir($tmp)) {
    fwrite(STDERR, "Unable to create temp directory\n");
    exit(1);
}

$zip = new ZipArchive();
if ($zip->open($zipPath) !== true) {
    fwrite(STDERR, "Unable to open module zip\n");
    exit(1);
}
if (!$zip->extractTo($tmp)) {
    $zip->close();
    fwrite(STDERR, "Unable to extract module zip\n");
    exit(1);
}
$zip->close();

$manifestEntry = (string) ($zipValidation['manifest_entry'] ?? '');
$moduleRoot = $tmp;
if ($manifestEntry !== '' && str_contains($manifestEntry, '/')) {
    $prefix = dirname($manifestEntry);
    $candidate = $tmp . '/' . $prefix;
    if (is_dir($candidate)) {
        $moduleRoot = $candidate;
    }
}

$manifestState = (new CoreModuleZipValidator())->readManifestFromExtracted($moduleRoot);
if (!(bool) ($manifestState['ok'] ?? false)) {
    fwrite(STDERR, "Manifest validation failed: " . implode(' | ', (array) ($manifestState['errors'] ?? [])) . "\n");
    exit(1);
}

$manifest = is_array($manifestState['manifest'] ?? null) ? $manifestState['manifest'] : [];
$release = is_array($manifest['release'] ?? null) ? $manifest['release'] : [];
$checksumsRelativePath = (string) ($release['checksums'] ?? '');
$signatureRelativePath = (string) ($release['signature'] ?? '');

$checksumState = (new CoreModuleChecksumValidator())->validate($moduleRoot, $checksumsRelativePath);
if (!((bool) ($checksumState['valid'] ?? false))) {
    fwrite(STDERR, "Checksums validation failed: " . implode(' | ', (array) ($checksumState['errors'] ?? [])) . "\n");
    exit(1);
}

$checksums = is_array($checksumState['checksums'] ?? null) ? $checksumState['checksums'] : [];
$signatureState = (new CoreModuleSignatureValidator())->validate($moduleRoot, $signatureRelativePath, $checksums);

if ($requireSignature && $publicKeyPath !== '') {
    if (!is_file($publicKeyPath)) {
        fwrite(STDERR, "Provided public key not found: {$publicKeyPath}\n");
        exit(1);
    }
    $signaturePath = rtrim($moduleRoot, '/') . '/signature.json';
    if ($signatureRelativePath !== '') {
        $candidate = $moduleRoot . '/' . ltrim(str_replace('\\', '/', $signatureRelativePath), '/');
        if (is_file($candidate)) {
            $signaturePath = $candidate;
        }
    }
    $signature = json_decode((string) file_get_contents($signaturePath), true);
    if (!is_array($signature)) {
        fwrite(STDERR, "Signature validation failed: signature.json invalide\n");
        exit(1);
    }
    $signedHash = strtolower(trim((string) ($signature['signed_hash'] ?? '')));
    $moduleHash = strtolower(trim((string) ($checksums['module_hash'] ?? '')));
    if ($signedHash === '' || $moduleHash === '' || !hash_equals($moduleHash, $signedHash)) {
        fwrite(STDERR, "Signature validation failed: signed_hash != module_hash\n");
        exit(1);
    }
    $raw = base64_decode((string) ($signature['signature'] ?? ''), true);
    if (!is_string($raw) || $raw === '') {
        fwrite(STDERR, "Signature validation failed: signature base64 invalide\n");
        exit(1);
    }
    $publicKey = (string) file_get_contents($publicKeyPath);
    $ok = openssl_verify($signedHash, $raw, $publicKey, OPENSSL_ALGO_SHA256);
    if ($ok !== 1) {
        fwrite(STDERR, "Signature validation failed: RSA verify KO avec clé fournie\n");
        exit(1);
    }
    $signatureState = [
        'status' => 'signed_valid',
        'valid' => true,
        'errors' => [],
        'key_id' => (string) ($signature['key_id'] ?? ''),
    ];
} elseif ($requireSignature && !((bool) ($signatureState['valid'] ?? false))) {
    fwrite(STDERR, "Signature validation failed: " . implode(' | ', (array) ($signatureState['errors'] ?? [])) . "\n");
    exit(1);
}

echo "Module release verified: " . basename($zipPath) . "\n";
echo "- checksums: valid\n";
echo "- signature: " . (string) ($signatureState['status'] ?? 'unsigned') . "\n";

$cleanup = static function (string $path): void {
    if (!is_dir($path)) {
        return;
    }
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($iterator as $entry) {
        if ($entry->isDir()) {
            @rmdir($entry->getPathname());
        } else {
            @unlink($entry->getPathname());
        }
    }
    @rmdir($path);
};
$cleanup($tmp);
