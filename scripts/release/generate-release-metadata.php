#!/usr/bin/env php
<?php

declare(strict_types=1);

if ($argc < 2) {
    fwrite(STDERR, "Usage: php generate-release-metadata.php <release-zip> [private-key.pem]\n");
    exit(1);
}

$zipFile = $argv[1];
if (!is_file($zipFile)) {
    fwrite(STDERR, "Release ZIP not found: {$zipFile}\n");
    exit(1);
}

$outDir = dirname($zipFile);
$baseName = pathinfo($zipFile, PATHINFO_FILENAME);

$sha256 = hash_file('sha256', $zipFile);
$sha512 = hash_file('sha512', $zipFile);

$checksums = [
    'schema' => 'catmin.release.checksums.v1',
    'generated_at' => gmdate('c'),
    'file' => basename($zipFile),
    'size_bytes' => filesize($zipFile) ?: 0,
    'sha256' => $sha256 ?: '',
    'sha512' => $sha512 ?: '',
];

$checksumsPath = $outDir . '/' . $baseName . '-checksums.json';
file_put_contents($checksumsPath, json_encode($checksums, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);

$signaturePath = $outDir . '/' . $baseName . '-signature.json';
$signaturePayload = [
    'schema' => 'catmin.release.signature.v1',
    'generated_at' => gmdate('c'),
    'file' => basename($zipFile),
    'algorithm' => 'rsa-sha256',
    'signed' => false,
    'signature_b64' => '',
];

if (isset($argv[2]) && is_file($argv[2])) {
    $privateKey = openssl_pkey_get_private((string) file_get_contents($argv[2]));
    if ($privateKey !== false) {
        $signed = '';
        $toSign = basename($zipFile) . ':' . ($sha256 ?: '');
        if (openssl_sign($toSign, $signed, $privateKey, OPENSSL_ALGO_SHA256)) {
            $signaturePayload['signed'] = true;
            $signaturePayload['signature_b64'] = base64_encode($signed);
        }
        openssl_pkey_free($privateKey);
    }
}

file_put_contents($signaturePath, json_encode($signaturePayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);

echo "Generated:\n- " . basename($checksumsPath) . "\n- " . basename($signaturePath) . "\n";

