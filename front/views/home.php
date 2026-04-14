<?php

declare(strict_types=1);

$version = '';
if (is_file(CATMIN_CORE . '/versioning/Version.php')) {
    require_once CATMIN_CORE . '/versioning/Version.php';
    if (class_exists('Core\\versioning\\Version')) {
        $version = (string) \Core\versioning\Version::current();
    }
}

$frontContext = is_array($frontContext ?? null) ? $frontContext : [];
$frontModules = is_array($frontContext['modules'] ?? null) ? $frontContext['modules'] : [];
$frontRegions = is_array($frontContext['regions'] ?? null) ? $frontContext['regions'] : [];
$frontAssets = is_array($frontContext['assets'] ?? null) ? $frontContext['assets'] : ['css' => [], 'js' => []];
$frontBridges = is_array($frontContext['bridges'] ?? null) ? $frontContext['bridges'] : [];
?><!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <title>CATMIN Front</title>
    <link rel="stylesheet" href="/odin-color.css?v=14">
    <link rel="stylesheet" href="/assets/vendor/bootstrap/5.3.8/css/bootstrap.min.css">
    <script src="/assets/js/odin-color.js?v=1"></script>
</head>
<body class="container py-5">
    <h1 class="mb-3">CATMIN Front</h1>
    <p class="mb-1">Interface publique minimale (V1 noindex) pilotée par Front Core Loader.</p>
    <small>Version: <?= htmlspecialchars($version !== '' ? $version : 'unknown', ENT_QUOTES, 'UTF-8') ?></small>

    <hr>
    <h2 class="h5">Front Runtime Snapshot</h2>
    <ul>
        <li>Modules front actifs: <?= count($frontModules) ?></li>
        <li>Régions déclarées: <?= count($frontRegions) ?></li>
        <li>Assets CSS déclarés: <?= count((array) ($frontAssets['css'] ?? [])) ?></li>
        <li>Assets JS déclarés: <?= count((array) ($frontAssets['js'] ?? [])) ?></li>
        <li>Endpoints bridge front: <?= count($frontBridges) ?></li>
    </ul>
</body>
</html>
