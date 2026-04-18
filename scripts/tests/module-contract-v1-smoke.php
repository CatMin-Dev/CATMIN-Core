<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/core/env.php';
require_once CATMIN_CORE . '/module-manifest-v1-schema.php';
require_once CATMIN_CORE . '/module-manifest-standard.php';
require_once CATMIN_CORE . '/module-validator.php';

$moduleDir = CATMIN_MODULES . '/admin/cat-contract-demo';
$manifestPath = $moduleDir . '/manifest.json';

if (!is_file($manifestPath)) {
    fwrite(STDERR, "Missing manifest: {$manifestPath}\n");
    exit(1);
}

$raw = file_get_contents($manifestPath);
$manifest = is_string($raw) ? json_decode($raw, true) : null;
if (!is_array($manifest)) {
    fwrite(STDERR, "Invalid JSON manifest\n");
    exit(1);
}

$v1 = (new CoreModuleManifestV1Schema())->validate($manifest);
if (!((bool) ($v1['valid'] ?? false))) {
    fwrite(STDERR, "V1 schema errors:\n- " . implode("\n- ", (array) ($v1['errors'] ?? [])) . "\n");
    exit(1);
}

$validator = new CoreModuleValidator();
$result = $validator->validate($manifest, $moduleDir);
if (!((bool) ($result['valid'] ?? false))) {
    fwrite(STDERR, "Validator errors:\n- " . implode("\n- ", (array) ($result['errors'] ?? [])) . "\n");
    exit(1);
}

$normalized = (array) ($result['normalized'] ?? []);
// Build required paths from mandatory fields + all declared route files
$requiredPaths = array_filter([
    (string) ($normalized['bootstrap']['provider'] ?? ''),
    (string) ($normalized['permissions']['file'] ?? ''),
    (string) ($normalized['settings']['file'] ?? ''),
    (string) ($normalized['docs']['index'] ?? ''),
    (string) ($normalized['release']['checksums'] ?? ''),
    (string) ($normalized['release']['signature'] ?? ''),
    (string) ($normalized['release']['versioning']['changelog'] ?? ''),
], static fn (string $v): bool => $v !== '');

// Add only the route files actually declared in the manifest (not hardcoded)
foreach ((array) ($normalized['routes_map'] ?? []) as $routeFile) {
    if (is_string($routeFile) && $routeFile !== '') {
        $requiredPaths[] = $routeFile;
    }
}

foreach ($requiredPaths as $relativePath) {
    if ($relativePath === '') {
        fwrite(STDERR, "Empty required contract path\n");
        exit(1);
    }

    $absolute = $moduleDir . '/' . ltrim($relativePath, '/');
    if (!is_file($absolute)) {
        fwrite(STDERR, "Missing declared file: {$relativePath}\n");
        exit(1);
    }
}

echo "OK: module contract V1 smoke\n";
