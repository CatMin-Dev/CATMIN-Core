<?php

declare(strict_types=1);

$version = '';
if (is_file(CATMIN_CORE . '/versioning/Version.php')) {
    require_once CATMIN_CORE . '/versioning/Version.php';
    if (class_exists('Core\\versioning\\Version')) {
        $version = (string) \Core\versioning\Version::current();
    }
}
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
    <p class="mb-1">Interface publique minimale (V1 noindex).</p>
    <small>Version: <?= htmlspecialchars($version !== '' ? $version : 'unknown', ENT_QUOTES, 'UTF-8') ?></small>
</body>
</html>
