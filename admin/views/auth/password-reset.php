<?php

declare(strict_types=1);

use Core\security\CsrfManager;

$mode = isset($mode) && (string) $mode === 'change' ? 'change' : 'reset';
$isChange = $mode === 'change';

ob_start();
?>
<div class="cat-auth-shell">
    <section class="cat-card cat-auth-card">
        <header class="cat-auth-header text-center">
            <img src="/assets/logo-color.png" alt="CATMIN" class="cat-auth-logo mb-2">
            <h1 class="h4 fw-bold mb-1"><?= $isChange ? 'Changer le mot de passe' : 'Reinitialiser le mot de passe' ?></h1>
            <p class="text-secondary mb-0"><?= $isChange ? 'Action sensible: reauth recente requise.' : 'Definis un nouveau mot de passe admin.' ?></p>
        </header>

        <div class="cat-auth-body-content">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger cat-alert" role="alert"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
            <?php if (!empty($message)): ?>
                <div class="alert alert-success cat-alert" role="alert"><?= htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <form class="cat-auth-form" method="post" action="<?= htmlspecialchars((string) ($adminBase ?? '/admin') . ($isChange ? '/password/change' : '/password/reset'), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars((new CsrfManager())->token(), ENT_QUOTES, 'UTF-8') ?>">

                <?php if ($isChange): ?>
                    <div class="cat-form-group mb-3">
                        <label class="form-label" for="current_password">Mot de passe actuel</label>
                        <div class="input-group">
                            <input class="form-control" id="current_password" name="current_password" type="password" autocomplete="current-password" required data-auth-autofocus>
                            <button class="btn btn-outline-secondary" type="button" data-password-toggle="#current_password" aria-label="Afficher/masquer le mot de passe actuel">
                                Voir
                            </button>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="cat-form-group mb-3">
                    <label class="form-label" for="password">Nouveau mot de passe</label>
                    <div class="input-group">
                        <input class="form-control" id="password" name="password" type="password" autocomplete="new-password" required <?= $isChange ? '' : 'data-auth-autofocus' ?>>
                        <button class="btn btn-outline-secondary" type="button" data-password-toggle="#password" aria-label="Afficher/masquer le mot de passe">
                            Voir
                        </button>
                    </div>
                    <div class="cat-password-strength mt-2" data-password-meter data-password-source="#password">
                        <div class="progress" role="progressbar" aria-label="Force du mot de passe" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar" data-password-meter-bar style="width: 0%"></div>
                        </div>
                        <small class="text-secondary" data-password-meter-label>Force: faible</small>
                    </div>
                </div>

                <div class="cat-form-group mb-3">
                    <label class="form-label" for="password_confirm">Confirmation</label>
                    <input class="form-control" id="password_confirm" name="password_confirm" type="password" autocomplete="new-password" required>
                </div>

                <button class="btn btn-catmin-login w-100 cat-btn" type="submit">Enregistrer</button>
            </form>

            <div class="text-center mt-3">
                <a class="small text-decoration-none" href="<?= htmlspecialchars((string) ($adminBase ?? '/admin') . ($isChange ? '/' : '/login'), ENT_QUOTES, 'UTF-8') ?>"><?= $isChange ? 'Retour admin' : 'Retour connexion' ?></a>
            </div>
        </div>
    </section>
</div>
<?php
$authTitle = $isChange ? 'Change password' : 'Reset password';
$authContent = (string) ob_get_clean();
require dirname(__DIR__) . '/layouts/auth.php';
