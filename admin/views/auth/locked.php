<?php

declare(strict_types=1);

$remaining = 300;

ob_start();
?>
<div class="cat-auth-shell">
    <section class="cat-card cat-auth-card">
        <header class="cat-auth-header text-center">
            <img src="/assets/logo-color.png" alt="CATMIN" class="cat-auth-logo mb-2">
            <h1 class="h4 fw-bold mb-1">Compte verrouille</h1>
            <p class="text-secondary mb-0">Trop de tentatives detectees. Patiente avant un nouvel essai.</p>
        </header>

        <div class="cat-auth-body-content text-center">
            <div class="alert alert-warning cat-alert mb-3" role="alert">
                Acces temporairement bloque pour protection.
            </div>
            <p class="mb-1">Nouvelle tentative dans :</p>
            <p class="h3 mb-3" data-lock-countdown="<?= (int) $remaining ?>">
                05:00
            </p>
            <a class="btn btn-outline-secondary cat-btn" href="<?= htmlspecialchars((string) ($adminBase ?? '/admin') . '/login', ENT_QUOTES, 'UTF-8') ?>">Reessayer</a>
        </div>
    </section>
</div>
<?php
$authTitle = 'Compte verrouille';
$authContent = (string) ob_get_clean();
require dirname(__DIR__) . '/layouts/auth.php';
