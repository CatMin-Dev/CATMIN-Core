#!/usr/bin/env php
<?php

declare(strict_types=1);

$root = realpath(__DIR__ . '/../../');
if (!is_string($root) || $root === '') {
    fwrite(STDERR, "Unable to resolve project root\n");
    exit(1);
}

require_once $root . '/bootstrap.php';

$output = $argv[1] ?? (CATMIN_MODULES . '/official-release-index.json');

$scanScope = static function (string $scopeDir): array {
    $rows = [];
    if (!is_dir($scopeDir)) {
        return $rows;
    }

    foreach (glob($scopeDir . '/*', GLOB_ONLYDIR) ?: [] as $moduleDir) {
        $manifestPath = $moduleDir . '/manifest.json';
        if (!is_file($manifestPath)) {
            continue;
        }

        $manifest = json_decode((string) file_get_contents($manifestPath), true);
        if (!is_array($manifest)) {
            continue;
        }

        $checksumsPath = $moduleDir . '/checksums.json';
        $moduleHash = '';
        if (is_file($checksumsPath)) {
            $checksums = json_decode((string) file_get_contents($checksumsPath), true);
            if (is_array($checksums)) {
                $moduleHash = strtolower(trim((string) ($checksums['module_hash'] ?? '')));
            }
        }

        $rows[] = [
            'slug' => strtolower(trim((string) ($manifest['slug'] ?? basename($moduleDir)))),
            'name' => (string) ($manifest['name'] ?? basename($moduleDir)),
            'version' => (string) ($manifest['version'] ?? '0.0.0'),
            'type' => strtolower(trim((string) ($manifest['type'] ?? basename(dirname($moduleDir))))),
            'author' => (string) ($manifest['author'] ?? ''),
            'release_channel' => strtolower(trim((string) ($manifest['release_channel'] ?? 'stable'))),
            'module_hash' => $moduleHash,
            'path' => str_replace('\\', '/', (string) substr($moduleDir, strlen(CATMIN_MODULES) + 1)),
        ];
    }

    return $rows;
};

$modules = [];
foreach (['core', 'admin', 'front', 'integration', 'driver'] as $scope) {
    $modules = array_merge($modules, $scanScope(CATMIN_MODULES . '/' . $scope));
}

usort($modules, static fn (array $a, array $b): int => strcmp(($a['type'] . '/' . $a['slug']), ($b['type'] . '/' . $b['slug'])));

$officialKeys = [];
foreach ((array) config('keyring.official', []) as $entry) {
    if (!is_array($entry)) {
        continue;
    }
    $officialKeys[] = [
        'key_id' => (string) ($entry['key_id'] ?? ''),
        'publisher' => (string) ($entry['publisher'] ?? ''),
        'scope' => (string) ($entry['scope'] ?? 'official'),
        'status' => (string) ($entry['status'] ?? 'active'),
        'algorithm' => (string) ($entry['algorithm'] ?? 'rsa-sha256'),
        'source' => (string) ($entry['source'] ?? 'embedded'),
    ];
}

$payload = [
    'schema_version' => 'catmin.official.modules-index.v1',
    'generated_at' => gmdate('c'),
    'signing_required' => true,
    'trust_mode' => (string) config('module-trust.mode', 'strict'),
    'official_publishers' => array_values((array) config('module-trust.official_publishers', [])),
    'official_keys' => $officialKeys,
    'modules' => $modules,
];

$dir = dirname($output);
if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
    fwrite(STDERR, "Unable to create output directory: {$dir}\n");
    exit(1);
}

$encoded = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
if (!is_string($encoded) || @file_put_contents($output, $encoded . PHP_EOL) === false) {
    fwrite(STDERR, "Unable to write output file: {$output}\n");
    exit(1);
}

echo "Official modules index generated: {$output}\n";
echo "Modules indexed: " . count($modules) . "\n";
