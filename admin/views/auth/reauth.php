<?php

declare(strict_types=1);

use Core\security\CsrfManager;

ob_start();
?>
<div class="cat-auth-shell">
    <section class="cat-card cat-auth-card">
        <header class="cat-auth-header text-center">
            <img src="/assets/logo-color.png" alt="CATMIN" class="cat-auth-logo mb-2">
            <h1 class="h4 fw-bold mb-1">Re-authentification</h1>
            <p class="text-secondary mb-0">Validation requise pour action sensible.</p>
        </header>

        <div class="cat-auth-body-content">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger cat-alert" role="alert"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <form class="cat-auth-form" method="post" action="<?= htmlspecialchars((string) ($adminBase ?? '/admin') . '/reauth', ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars((new CsrfManager())->token(), ENT_QUOTES, 'UTF-8') ?>">

                <div class="cat-form-group mb-3">
                    <label class="form-label" for="password">Mot de passe</label>
                    <div class="input-group">
                        <input class="form-control" id="password" name="password" type="password" autocomplete="current-password" required data-auth-autofocus>
                        <button class="btn btn-outline-secondary" type="button" data-password-toggle="#password" aria-label="Afficher/masquer le mot de passe">
                            Voir
                        </button>
                    </div>
                </div>

                <button class="btn btn-catmin-login w-100 cat-btn" type="submit">Valider</button>
            </form>

            <div class="text-center mt-3">
                <a class="small text-decoration-none" href="<?= htmlspecialchars((string) ($adminBase ?? '/admin') . '/logout', ENT_QUOTES, 'UTF-8') ?>">Se deconnecter</a>
            </div>
        </div>
    </section>
</div>
<?php
$authTitle = 'Re-auth';
$authContent = (string) ob_get_clean();
require dirname(__DIR__) . '/layouts/auth.php';
