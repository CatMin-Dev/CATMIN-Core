<?php

declare(strict_types=1);

use Core\security\CsrfManager;
?><!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>CATMIN Re-Auth</title>
    <link rel="stylesheet" href="/odin-color.css">
    <link rel="stylesheet" href="/assets/vendor/bootstrap/5.3.8/css/bootstrap.min.css">
</head>
<body class="container py-5">
<h1 class="mb-3">Re-authentification</h1>
<?php if (!empty($error)): ?>
    <p style="color:#b42318;"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>
<form method="post" action="<?= htmlspecialchars((string) ($adminBase ?? '/admin') . '/reauth', ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars((new CsrfManager())->token(), ENT_QUOTES, 'UTF-8') ?>">
    <div class="mb-3">
        <label for="password">Mot de passe</label>
        <input id="password" name="password" type="password" required>
    </div>
    <button type="submit">Valider</button>
</form>
</body>
</html>
