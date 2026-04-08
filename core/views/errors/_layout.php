<?php

declare(strict_types=1);

$status = (int) ($status ?? 500);
$title = (string) ($title ?? 'Erreur');
$message = (string) ($message ?? 'Une erreur est survenue.');
$homeUrl = (string) ($home_url ?? '/');
$adminLogin = (string) ($admin_login ?? '/admin/login');
?><!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <title>CATMIN · <?= htmlspecialchars((string) $status, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="icon" type="image/png" href="/favicon.png">
    <link rel="stylesheet" href="/assets/vendor/bootstrap/5.3.8/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/odin-color.css?v=14">
</head>
<body class="min-vh-100 d-flex align-items-center justify-content-center p-3" style="background:linear-gradient(145deg,#9a1b3d 0%,#70142d 45%,#292524 100%);">
    <main class="card shadow-lg border-0" style="max-width:720px;width:100%;">
        <div class="card-body p-4 p-lg-5">
            <span class="badge text-bg-dark mb-3">CATMIN FAILSAFE</span>
            <h1 class="display-6 fw-bold mb-2"><?= htmlspecialchars((string) $status, ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="text-secondary mb-4"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-primary" href="<?= htmlspecialchars($homeUrl, ENT_QUOTES, 'UTF-8') ?>">Accueil</a>
                <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($adminLogin, ENT_QUOTES, 'UTF-8') ?>">Login admin</a>
            </div>
        </div>
    </main>
</body>
</html>
