<?php

declare(strict_types=1);

$manifestPath = dirname(__DIR__, 2) . '/manifest.json';
$manifestRaw = is_file($manifestPath) ? file_get_contents($manifestPath) : false;
$manifest = is_string($manifestRaw) ? json_decode($manifestRaw, true) : null;
$moduleRoot = dirname(__DIR__, 2);

if (!is_array($manifest)) {
    fwrite(STDERR, "Manifest unreadable\n");
    exit(1);
}

$required = ['schema_version', 'module_id', 'routes', 'permissions', 'settings', 'navigation', 'ui', 'docs', 'release'];
foreach ($required as $key) {
    if (!array_key_exists($key, $manifest)) {
        fwrite(STDERR, "Missing key: {$key}\n");
        exit(1);
    }
}

$requiredDirs = ['assets', 'docs', 'routes', 'services', 'tests', 'views', 'snippets', 'bridge'];
foreach ($requiredDirs as $dir) {
    if (!is_dir($moduleRoot . '/' . $dir)) {
        fwrite(STDERR, "Missing required directory: {$dir}\n");
        exit(1);
    }
}

$requiredSocleFiles = [
    'views/front/block.php',
    'snippets/card.php',
    'bridge/admin.bridge.php',
    'bridge/front.bridge.php',
];
foreach ($requiredSocleFiles as $file) {
    if (!is_file($moduleRoot . '/' . $file)) {
        fwrite(STDERR, "Missing required socle file: {$file}\n");
        exit(1);
    }
}

$navigation = is_array($manifest['navigation'] ?? null) ? $manifest['navigation'] : [];
if (!is_array($navigation['sidebar'] ?? null) || $navigation['sidebar'] === []) {
    fwrite(STDERR, "navigation.sidebar missing or empty\n");
    exit(1);
}

if (!is_array($navigation['settings_sidebar'] ?? null) || $navigation['settings_sidebar'] === []) {
    fwrite(STDERR, "navigation.settings_sidebar missing or empty\n");
    exit(1);
}

$ui = is_array($manifest['ui'] ?? null) ? $manifest['ui'] : [];
if (!array_key_exists('inject', $ui) || !is_array($ui['inject'])) {
    fwrite(STDERR, "ui.inject missing or invalid\n");
    exit(1);
}

$release = is_array($manifest['release'] ?? null) ? $manifest['release'] : [];
if (!isset($release['checksums'], $release['signature'], $release['versioning'])) {
    fwrite(STDERR, "Missing release fields\n");
    exit(1);
}

$versioning = is_array($release['versioning'] ?? null) ? $release['versioning'] : [];
if (!isset($versioning['strategy'], $versioning['changelog'])) {
    fwrite(STDERR, "Missing release versioning fields\n");
    exit(1);
}

echo "OK module boot smoke\n";
