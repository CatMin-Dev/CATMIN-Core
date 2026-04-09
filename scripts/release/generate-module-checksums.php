#!/usr/bin/env php
<?php

declare(strict_types=1);

if ($argc < 2) {
    fwrite(STDERR, "Usage: php generate-module-checksums.php <module-dir> [output-json]\n");
    exit(1);
}

$moduleDir = rtrim($argv[1], '/');
if (!is_dir($moduleDir)) {
    fwrite(STDERR, "Module directory not found: {$moduleDir}\n");
    exit(1);
}

$output = $argv[2] ?? ($moduleDir . '/checksums.json');
$manifestPath = $moduleDir . '/manifest.json';
if (!is_file($manifestPath)) {
    fwrite(STDERR, "manifest.json missing in module directory\n");
    exit(1);
}

$manifest = json_decode((string) file_get_contents($manifestPath), true);
if (!is_array($manifest)) {
    fwrite(STDERR, "manifest.json invalid\n");
    exit(1);
}

$moduleSlug = strtolower(trim((string) ($manifest['slug'] ?? basename($moduleDir))));
$moduleVersion = trim((string) ($manifest['version'] ?? '0.0.0'));

$excludeNames = [
    'checksums.json',
    'signature.json',
    'release-metadata.json',
];
$excludeFragments = [
    '/.git/',
    '/.github/',
    '/.vscode/',
    '/__macosx/',
    '/cache/',
    '/tmp/',
    '/backups/',
];
$excludeSuffixes = ['.key', '.pem', '.p12', '.pfx'];

$root = str_replace('\\', '/', $moduleDir);
$files = [];

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($moduleDir, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::LEAVES_ONLY
);

foreach ($iterator as $item) {
    if (!$item->isFile()) {
        continue;
    }

    $full = str_replace('\\', '/', $item->getPathname());
    $relative = ltrim(str_replace($root, '', $full), '/');
    if ($relative === '') {
        continue;
    }

    $basename = basename($relative);
    if (in_array($basename, $excludeNames, true)) {
        continue;
    }

    $lowerRelative = strtolower('/' . $relative);
    $skip = false;
    foreach ($excludeFragments as $fragment) {
        if (str_contains($lowerRelative, $fragment)) {
            $skip = true;
            break;
        }
    }
    if ($skip) {
        continue;
    }

    $lowerBase = strtolower($basename);
    foreach ($excludeSuffixes as $suffix) {
        if (str_ends_with($lowerBase, $suffix)) {
            $skip = true;
            break;
        }
    }
    if ($skip) {
        continue;
    }

    $relative = str_replace('\\', '/', $relative);
    $files[$relative] = strtolower((string) hash_file('sha256', $full));
}

ksort($files);
if ($files === []) {
    fwrite(STDERR, "No files eligible for checksum generation\n");
    exit(1);
}

$pairs = [];
foreach ($files as $path => $hash) {
    $pairs[] = $path . ':' . $hash;
}
$moduleHash = hash('sha256', implode("\n", $pairs));

$payload = [
    'schema_version' => '1.0.0',
    'algorithm' => 'sha256',
    'module_slug' => $moduleSlug,
    'module_version' => $moduleVersion,
    'generated_at' => gmdate('c'),
    'files' => $files,
    'module_hash' => $moduleHash,
];

$written = @file_put_contents(
    $output,
    json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL
);
if ($written === false) {
    fwrite(STDERR, "Unable to write checksums file: {$output}\n");
    exit(1);
}

echo "Checksums generated: {$output}\n";
