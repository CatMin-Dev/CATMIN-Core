#!/usr/bin/env php
<?php

declare(strict_types=1);

if ($argc < 4) {
    fwrite(STDERR, "Usage: php generate-module-signature.php <checksums.json> <private-key.pem> <key-id> [output-json]\n");
    exit(1);
}

$checksumsPath = $argv[1];
$privateKeyPath = $argv[2];
$keyId = trim((string) $argv[3]);
$output = $argv[4] ?? (dirname($checksumsPath) . '/signature.json');

if (!is_file($checksumsPath)) {
    fwrite(STDERR, "checksums.json not found: {$checksumsPath}\n");
    exit(1);
}
if (!is_file($privateKeyPath)) {
    fwrite(STDERR, "Private key not found: {$privateKeyPath}\n");
    exit(1);
}
if ($keyId === '') {
    fwrite(STDERR, "key-id is required\n");
    exit(1);
}

$checksums = json_decode((string) file_get_contents($checksumsPath), true);
if (!is_array($checksums)) {
    fwrite(STDERR, "Invalid checksums.json\n");
    exit(1);
}

$moduleHash = strtolower(trim((string) ($checksums['module_hash'] ?? '')));
if ($moduleHash === '' || preg_match('/^[a-f0-9]{64}$/', $moduleHash) !== 1) {
    fwrite(STDERR, "Invalid or missing module_hash\n");
    exit(1);
}

$privatePem = (string) file_get_contents($privateKeyPath);
$privateKey = openssl_pkey_get_private($privatePem);
if ($privateKey === false) {
    fwrite(STDERR, "Unable to read private key\n");
    exit(1);
}

$signatureRaw = '';
$ok = openssl_sign($moduleHash, $signatureRaw, $privateKey, OPENSSL_ALGO_SHA256);
openssl_pkey_free($privateKey);
if (!$ok || $signatureRaw === '') {
    fwrite(STDERR, "RSA sign failed\n");
    exit(1);
}

$payload = [
    'schema_version' => '1.0.0',
    'algorithm' => 'rsa-sha256',
    'module_slug' => (string) ($checksums['module_slug'] ?? ''),
    'module_version' => (string) ($checksums['module_version'] ?? ''),
    'signed_hash' => $moduleHash,
    'signature' => base64_encode($signatureRaw),
    'key_id' => $keyId,
    'signed_at' => gmdate('c'),
];

$written = @file_put_contents(
    $output,
    json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL
);
if ($written === false) {
    fwrite(STDERR, "Unable to write signature file: {$output}\n");
    exit(1);
}

echo "Signature generated: {$output}\n";
