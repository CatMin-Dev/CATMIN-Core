<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once CATMIN_CORE . '/module-loader.php';
require_once CATMIN_CORE . '/module-integrity.php';
require_once CATMIN_CORE . '/router.php';

$scan = (new CoreModuleLoader())->scan();
$modules = (array) ($scan['modules'] ?? []);
$target = null;

foreach ($modules as $module) {
    $manifest = (array) ($module['manifest'] ?? []);
    if (strtolower(trim((string) ($manifest['slug'] ?? ''))) === 'cat-contract-demo') {
        $target = $module;
        break;
    }
}

if (!is_array($target)) {
    fwrite(STDERR, "cat-contract-demo not discovered by loader\n");
    exit(1);
}

if (!((bool) ($target['valid'] ?? false))) {
    fwrite(STDERR, "cat-contract-demo is invalid in loader snapshot\n");
    exit(1);
}

$manifest = (array) ($target['manifest'] ?? []);
$routesMap = (array) ($manifest['routes_map'] ?? []);
// Only check routes actually declared in the manifest
foreach (['admin', 'settings'] as $requiredZone) {
    if (trim((string) ($routesMap[$requiredZone] ?? '')) === '') {
        fwrite(STDERR, "Missing required routes_map zone: {$requiredZone}\n");
        exit(1);
    }
}

$integrity = (new CoreModuleIntegrity())->verify(
    (string) ($target['path'] ?? ''),
    (array) ($target['manifest'] ?? [])
);

if (strtolower((string) ($integrity['integrity']['status'] ?? '')) === 'missing_checksums') {
    fwrite(STDERR, "Integrity still reports missing checksums for cat-contract-demo\n");
    exit(1);
}

$router = Router::runtime();
$ref = new ReflectionClass($router);
$loadRoutes = $ref->getMethod('loadRoutesIfNeeded');
$loadRoutes->setAccessible(true);
$loadRoutes->invoke($router);

$collectionProp = $ref->getProperty('collection');
$collectionProp->setAccessible(true);
/** @var RouteCollection $collection */
$collection = $collectionProp->getValue($router);

$expectedByZone = [
    'admin' => ['/contract-demo', '/settings/contract-demo'],
];

$registered = [];
foreach (['GET', 'POST'] as $method) {
    foreach ($collection->routesForMethod($method) as $route) {
        if ((string) ($route['module'] ?? '') !== 'cat-contract-demo') {
            continue;
        }
        $registered[(string) ($route['zone'] ?? 'front') . '::' . (string) ($route['path'] ?? '')] = true;
    }
}

foreach ($expectedByZone as $zone => $paths) {
    foreach ($paths as $path) {
        if (!isset($registered[$zone . '::' . $path])) {
            fwrite(STDERR, "Missing registered module route {$zone} {$path}\n");
            exit(1);
        }
    }
}

echo "OK: module loader contract smoke\n";
