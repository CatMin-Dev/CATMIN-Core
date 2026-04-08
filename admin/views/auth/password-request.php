<?php

declare(strict_types=1);

use Core\security\CsrfManager;

ob_start();
?>
<div class="cat-auth-shell">
    <section class="cat-card cat-auth-card">
        <header class="cat-auth-header text-center">
            <img src="/assets/logo-color.png" alt="CATMIN" class="cat-auth-logo mb-2">
            <h1 class="h4 fw-bold mb-1">Mot de passe oublie</h1>
            <p class="text-secondary mb-0">Saisis ton email admin pour recevoir les instructions.</p>
        </header>

        <div class="cat-auth-body-content">
            <?php if (!empty($message)): ?>
                <div class="alert alert-info cat-alert" role="alert"><?= htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <form class="cat-auth-form" method="post" action="<?= htmlspecialchars((string) ($adminBase ?? '/admin') . '/password/request', ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars((new CsrfManager())->token(), ENT_QUOTES, 'UTF-8') ?>">

                <div class="cat-form-group mb-3">
                    <label class="form-label" for="email">Email</label>
                    <input class="form-control" id="email" name="email" type="email" autocomplete="email" required data-auth-autofocus>
                </div>

                <button class="btn btn-catmin-login w-100 cat-btn" type="submit">Envoyer</button>
            </form>

            <div class="text-center mt-3">
                <a class="small text-decoration-none" href="<?= htmlspecialchars((string) ($adminBase ?? '/admin') . '/login', ENT_QUOTES, 'UTF-8') ?>">Retour connexion</a>
            </div>
        </div>
    </section>
</div>
<?php
$authTitle = 'Demande reset';
$authContent = (string) ob_get_clean();
require dirname(__DIR__) . '/layouts/auth.php';
