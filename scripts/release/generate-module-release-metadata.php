#!/usr/bin/env php
<?php

declare(strict_types=1);

if ($argc < 2) {
    fwrite(STDERR, "Usage: php generate-module-release-metadata.php <release-dir> [output-json]\n");
    exit(1);
}

$releaseDir = rtrim($argv[1], '/');
if (!is_dir($releaseDir)) {
    fwrite(STDERR, "Release directory not found: {$releaseDir}\n");
    exit(1);
}

$output = $argv[2] ?? ($releaseDir . '/release-metadata.json');

$required = ['module.zip', 'manifest.json', 'checksums.json'];
$optional = ['signature.json'];

$missing = [];
foreach ($required as $file) {
    if (!is_file($releaseDir . '/' . $file)) {
        $missing[] = $file;
    }
}
if ($missing !== []) {
    fwrite(STDERR, 'Missing required artifacts: ' . implode(', ', $missing) . "\n");
    exit(1);
}

$manifest = json_decode((string) file_get_contents($releaseDir . '/manifest.json'), true);
$checksums = json_decode((string) file_get_contents($releaseDir . '/checksums.json'), true);

if (!is_array($manifest) || !is_array($checksums)) {
    fwrite(STDERR, "Invalid manifest/checksums json\n");
    exit(1);
}

$artifacts = [];
foreach (array_merge($required, $optional) as $file) {
    $path = $releaseDir . '/' . $file;
    if (!is_file($path)) {
        continue;
    }
    $artifacts[$file] = [
        'size_bytes' => (int) (filesize($path) ?: 0),
        'sha256' => (string) (hash_file('sha256', $path) ?: ''),
    ];
}

$payload = [
    'schema_version' => '1.0.0',
    'pipeline' => 'catmin.module.release.v1',
    'generated_at' => gmdate('c'),
    'module' => [
        'slug' => (string) ($manifest['slug'] ?? ''),
        'name' => (string) ($manifest['name'] ?? ''),
        'version' => (string) ($manifest['version'] ?? ''),
        'type' => (string) ($manifest['type'] ?? ''),
    ],
    'integrity' => [
        'algorithm' => 'sha256',
        'module_hash' => (string) ($checksums['module_hash'] ?? ''),
    ],
    'artifacts' => $artifacts,
];

$written = @file_put_contents(
    $output,
    json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL
);
if ($written === false) {
    fwrite(STDERR, "Unable to write release metadata: {$output}\n");
    exit(1);
}

echo "Release metadata generated: {$output}\n";
