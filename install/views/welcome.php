<?php

declare(strict_types=1);
$installRoot = isset($installRoot) && is_string($installRoot) && $installRoot !== '' ? rtrim($installRoot, '/') : '/install';
?><!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>CATMIN Installer</title>
</head>
<body>
<p>Installer bootstrap is active. Continue on <a href="<?= htmlspecialchars($installRoot, ENT_QUOTES, 'UTF-8') ?>/step?s=precheck">wizard step precheck</a>.</p>
</body>
</html>
