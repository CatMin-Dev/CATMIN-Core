<?php

declare(strict_types=1);

use Core\versioning\Version;
?><!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>CATMIN Admin</title>
    <link rel="stylesheet" href="/odin-color.css">
    <link rel="stylesheet" href="/assets/vendor/bootstrap/5.3.8/css/bootstrap.min.css">
</head>
<body class="container py-5">
<h1 class="mb-3">CATMIN Admin</h1>
<p class="mb-1">Auth admin native active.</p>
<?php if (!empty($user) && is_array($user)): ?>
    <p class="mb-1">Connecte en tant que: <strong><?= htmlspecialchars((string) ($user['username'] ?? $user['email'] ?? 'admin'), ENT_QUOTES, 'UTF-8') ?></strong></p>
<?php endif; ?>
<p><a href="<?= htmlspecialchars((string) ($adminBase ?? '/admin') . '/logout', ENT_QUOTES, 'UTF-8') ?>">Se deconnecter</a></p>
<small>Version: <?= htmlspecialchars(Version::current(), ENT_QUOTES, 'UTF-8') ?></small>
</body>
</html>
