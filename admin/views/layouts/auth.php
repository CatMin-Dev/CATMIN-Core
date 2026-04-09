<?php

declare(strict_types=1);

$authTitle = isset($authTitle) ? (string) $authTitle : 'Authentification';
$authContent = isset($authContent) ? (string) $authContent : '';
?><!doctype html>
<html lang="fr" data-bs-theme="corporate">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= htmlspecialchars('CATMIN Admin - ' . $authTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="icon" type="image/png" href="/favicon.png">
    <link rel="stylesheet" href="/assets/vendor/bootstrap/5.3.8/css/bootstrap.min.css">
    <link rel="stylesheet" href="/odin-color.css?v=14">
    <link rel="stylesheet" href="/assets/css/catmin-ui.css?v=7">
    <link rel="stylesheet" href="/assets/css/admin-login.css?v=2">
    <link rel="stylesheet" href="/assets/css/catmin-auth.css?v=1">
    <script src="/assets/js/odin-color.js?v=2"></script>
</head>
<body class="cat-auth-body admin-login-page">
<main class="cat-auth-main">
    <?= $authContent ?>
</main>
<footer class="admin-login-footer">© <?= date('Y') ?> CATMIN. <?= htmlspecialchars(__('footer.rights'), ENT_QUOTES, 'UTF-8') ?></footer>
<script src="/assets/js/catmin-auth.js?v=2"></script>
</body>
</html>
