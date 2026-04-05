<?php

declare(strict_types=1);

use Core\versioning\Version;
?><!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>CATMIN Front</title>
    <link rel="stylesheet" href="/odin-color.css">
    <link rel="stylesheet" href="/assets/vendor/bootstrap/5.3.8/css/bootstrap.min.css">
</head>
<body class="container py-5">
    <h1 class="mb-3">CATMIN Front</h1>
    <p class="mb-1">Interface publique minimale (V1 noindex).</p>
    <small>Version: <?= htmlspecialchars(Version::current(), ENT_QUOTES, 'UTF-8') ?></small>
</body>
</html>
