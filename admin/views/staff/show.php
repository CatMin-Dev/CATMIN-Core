<?php

declare(strict_types=1);

use Core\security\CsrfManager;

$pageTitle = 'Fiche compte';
$pageDescription = 'Detail complet du compte admin/staff.';
$activeNav = 'staff';
$breadcrumbs = [
    ['label' => 'Admin', 'href' => $adminBase . '/'],
    ['label' => 'Staff / Admins', 'href' => $adminBase . '/staff'],
    ['label' => (string) ($staff['username'] ?? 'Compte')],
];
$pageActions = [
    ['label' => 'Editer', 'href' => $adminBase . '/staff/' . (int) ($staff['id'] ?? 0) . '/edit', 'class' => 'btn btn-primary btn-sm'],
];

$isSuperAdmin = ((string) ($staff['role_slug'] ?? '') === 'super-admin');
$isActive = ((int) ($staff['is_active'] ?? 0)) === 1;

ob_start();
?>
<section class="card">
    <div class="card-body d-flex flex-wrap justify-content-between gap-3">
        <div>
            <h2 class="h5 mb-1"><?= htmlspecialchars((string) ($staff['username'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></h2>
            <p class="text-body-secondary mb-0"><?= htmlspecialchars((string) ($staff['email'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <div class="d-flex gap-2 align-items-start flex-wrap">
            <span class="badge text-bg-info"><?= htmlspecialchars((string) ($staff['role_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
            <span class="badge <?= $isActive ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= $isActive ? 'Actif' : 'Inactif' ?></span>
            <?php if ($isSuperAdmin): ?><span class="badge text-bg-danger">SuperAdmin</span><?php endif; ?>
        </div>
    </div>
</section>

<section class="row g-3">
    <div class="col-12 col-xl-4"><?php require __DIR__ . '/partials/details-card.php'; ?></div>
    <div class="col-12 col-xl-4"><?php require __DIR__ . '/partials/roles-card.php'; ?></div>
    <div class="col-12 col-xl-4">
        <section class="card h-100">
            <div class="card-header bg-transparent border-0 pt-3"><h3 class="h6 mb-0">Securite</h3></div>
            <div class="card-body pt-2">
                <?php if ($isSuperAdmin): ?>
                    <div class="alert alert-warning py-2">Reset superadmin hors UI.</div>
                <?php else: ?>
                    <form method="post" action="<?= htmlspecialchars((string) ($adminBase . '/staff/' . (int) ($staff['id'] ?? 0) . '/password'), ENT_QUOTES, 'UTF-8') ?>" class="d-grid gap-2">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars((new CsrfManager())->token(), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="password" class="form-control" name="password" minlength="12" placeholder="Nouveau mot de passe" required>
                        <input type="password" class="form-control" name="password_confirm" minlength="12" placeholder="Confirmation" required>
                        <button class="btn btn-outline-primary btn-sm" type="submit">Mettre a jour mot de passe</button>
                    </form>
                <?php endif; ?>
            </div>
        </section>
    </div>
</section>

<section class="row g-3">
    <div class="col-12"><?php require __DIR__ . '/partials/activity-card.php'; ?></div>
</section>

<script src="/assets/js/catmin-staff.js?v=1"></script>
<?php
$content = (string) ob_get_clean();
require CATMIN_ADMIN . '/views/layouts/admin.php';
