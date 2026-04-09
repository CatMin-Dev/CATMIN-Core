<?php

declare(strict_types=1);

use Core\security\CsrfManager;

$appAuthModules = [];
$moduleDirs = glob(CATMIN_MODULES . '/*', GLOB_ONLYDIR);
if (is_array($moduleDirs)) {
    foreach ($moduleDirs as $dir) {
        $name = strtolower(trim(basename($dir)));
        if ($name === '') {
            continue;
        }
        if (preg_match('/(oauth|sso|social|google|github|oidc|auth)/', $name) === 1) {
            $appAuthModules[] = $name;
        }
    }
}
$showAppAuthBlock = $appAuthModules !== [];

ob_start();
?>
<div class="cat-auth-shell">
    <section class="cat-card cat-auth-card">
        <header class="cat-auth-header text-center">
            <img src="/assets/logo-color.png" alt="CATMIN" class="cat-auth-logo mb-2">
            <h1 class="h4 fw-bold mb-1">Connexion Admin</h1>
            <p class="text-secondary mb-0">Acces securise a l'interface CATMIN.</p>
        </header>

        <div class="cat-auth-body-content">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger cat-alert" role="alert"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <form class="cat-auth-form" method="post" action="<?= htmlspecialchars((string) ($adminBase ?? '/admin') . '/login', ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars((new CsrfManager())->token(), ENT_QUOTES, 'UTF-8') ?>">

                <div class="cat-form-group mb-3">
                    <label class="form-label" for="identifier">Email ou username</label>
                    <input class="form-control" id="identifier" name="identifier" type="text" autocomplete="username" required data-auth-autofocus>
                </div>

                <div class="cat-form-group mb-3">
                    <label class="form-label" for="password">Mot de passe</label>
                    <div class="input-group">
                        <input class="form-control" id="password" name="password" type="password" autocomplete="current-password" required>
                        <button class="btn btn-outline-secondary" type="button" data-password-toggle="#password" aria-label="Afficher le mot de passe" aria-pressed="false" title="Afficher/masquer le mot de passe">
                            <span data-password-eye aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </span>
                            <span data-password-eye-slash class="d-none" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                    <path d="M3 3l18 18"></path>
                                </svg>
                            </span>
                            <span class="visually-hidden">Afficher/Masquer</span>
                        </button>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                    <span class="small text-body-secondary">Reset mot de passe: superadmin uniquement via gestion interne.</span>
                    <a class="small text-decoration-none" href="<?= htmlspecialchars((string) ($adminBase ?? '/admin') . '/locked', ENT_QUOTES, 'UTF-8') ?>">Compte verrouillé</a>
                </div>

                <button class="btn btn-catmin-login w-100 cat-btn" type="submit">Se connecter</button>
            </form>

            <?php if ($showAppAuthBlock): ?>
                <div class="admin-app-auth-placeholder mt-3">
                    <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                        <strong>Connexion via APP</strong>
                        <span class="badge text-bg-dark">Bientot disponible</span>
                    </div>
                    <p class="small text-secondary mb-0 mt-2">Zone reservee aux connecteurs OAuth/SSO (Google, GitHub, etc.) quand les modules sont actives.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>
<?php
$authTitle = 'Connexion';
$authContent = (string) ob_get_clean();
require dirname(__DIR__) . '/layouts/auth.php';
