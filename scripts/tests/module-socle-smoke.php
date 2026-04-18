<?php

declare(strict_types=1);

if ($argc < 2) {
    fwrite(STDERR, "Usage: php scripts/tests/module-socle-smoke.php <module-path>\n");
    exit(1);
}

$modulePathArg = trim((string) $argv[1]);
$moduleRoot = realpath($modulePathArg);
if (!is_string($moduleRoot) || $moduleRoot === '' || !is_dir($moduleRoot)) {
    fwrite(STDERR, "Invalid module path: {$modulePathArg}\n");
    exit(1);
}

$manifestPath = $moduleRoot . '/manifest.json';
$raw = is_file($manifestPath) ? file_get_contents($manifestPath) : false;
$manifest = is_string($raw) ? json_decode($raw, true) : null;

if (!is_array($manifest)) {
    fwrite(STDERR, "Manifest unreadable: {$manifestPath}\n");
    exit(1);
}

$errors = [];

$requiredTopKeys = ['schema_version', 'module_id', 'routes', 'permissions', 'settings', 'navigation', 'ui', 'docs', 'release'];
foreach ($requiredTopKeys as $key) {
    if (!array_key_exists($key, $manifest)) {
        $errors[] = 'Missing manifest key: ' . $key;
    }
}

$requiredDirs = ['assets', 'docs', 'routes', 'services', 'tests', 'views', 'snippets', 'bridge'];
foreach ($requiredDirs as $dir) {
    if (!is_dir($moduleRoot . '/' . $dir)) {
        $errors[] = 'Missing required directory: ' . $dir;
    }
}

$routes = is_array($manifest['routes'] ?? null) ? $manifest['routes'] : [];
foreach (['admin', 'settings'] as $zone) {
    $file = trim((string) ($routes[$zone] ?? ''));
    if ($file === '') {
        $errors[] = 'Missing routes.' . $zone;
        continue;
    }
    if (!is_file($moduleRoot . '/' . ltrim($file, '/'))) {
        $errors[] = 'Missing route file: ' . $file;
    }
}

$permissions = is_array($manifest['permissions'] ?? null) ? $manifest['permissions'] : [];
$permissionsFile = trim((string) ($permissions['file'] ?? ''));
if ($permissionsFile === '' || !is_file($moduleRoot . '/' . ltrim($permissionsFile, '/'))) {
    $errors[] = 'Missing permissions file declared by manifest';
}

$settings = is_array($manifest['settings'] ?? null) ? $manifest['settings'] : [];
$settingsFile = trim((string) ($settings['file'] ?? ''));
if ($settingsFile === '' || !is_file($moduleRoot . '/' . ltrim($settingsFile, '/'))) {
    $errors[] = 'Missing settings file declared by manifest';
}

$navigation = is_array($manifest['navigation'] ?? null) ? $manifest['navigation'] : [];
$sidebar = $navigation['sidebar'] ?? null;
$settingsSidebar = $navigation['settings_sidebar'] ?? null;
if (!is_array($sidebar) || $sidebar === []) {
    $errors[] = 'navigation.sidebar must exist and contain at least one item';
}
if (!is_array($settingsSidebar) || $settingsSidebar === []) {
    $errors[] = 'navigation.settings_sidebar must exist and contain at least one item';
}

$ui = is_array($manifest['ui'] ?? null) ? $manifest['ui'] : [];
if (!array_key_exists('inject', $ui) || !is_array($ui['inject'])) {
    $errors[] = 'ui.inject must exist and be an array';
}

$requiredFiles = [
    'views/front/block.php',
    'snippets/card.php',
    'bridge/admin.bridge.php',
    'bridge/front.bridge.php',
    'tests/Smoke/ModuleBootTest.php',
];

foreach ($requiredFiles as $file) {
    if (!is_file($moduleRoot . '/' . $file)) {
        $errors[] = 'Missing required socle file: ' . $file;
    }
}

if ($errors !== []) {
    fwrite(STDERR, "SOCLE CHECK FAILED for {$moduleRoot}\n");
    foreach ($errors as $error) {
        fwrite(STDERR, '- ' . $error . "\n");
    }
    exit(1);
}

echo "OK: module socle smoke\n";
