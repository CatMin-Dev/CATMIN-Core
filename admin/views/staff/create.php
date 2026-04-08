<?php

declare(strict_types=1);

use Core\security\CsrfManager;

$pageTitle = 'Creer un compte';
$pageDescription = 'Nouveau compte staff/admin avec role et statut controles.';
$activeNav = 'staff';
$breadcrumbs = [
    ['label' => 'Admin', 'href' => $adminBase . '/'],
    ['label' => 'Staff / Admins', 'href' => $adminBase . '/staff'],
    ['label' => 'Creation'],
];
$pageActions = [];

ob_start();
?>
<form method="post" action="<?= htmlspecialchars((string) ($adminBase . '/staff/create'), ENT_QUOTES, 'UTF-8') ?>" class="row g-3">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars((new CsrfManager())->token(), ENT_QUOTES, 'UTF-8') ?>">
    <div class="col-12 col-xl-8">
        <?php $isEdit = false; $isSuperAdmin = false; require __DIR__ . '/partials/form.php'; ?>
    </div>
    <div class="col-12 col-xl-4">
        <div class="card h-100"><div class="card-body">
            <h3 class="h6">Regles securite</h3>
            <ul class="small text-body-secondary mb-0">
                <li>Mot de passe fort (12+).</li>
                <li>Role attribue explicitement.</li>
                <li>SuperAdmin reserve au core.</li>
            </ul>
        </div></div>
    </div>
    <div class="col-12 d-flex gap-2">
        <button class="btn btn-primary" type="submit">Creer</button>
        <a class="btn btn-outline-secondary" href="<?= htmlspecialchars((string) ($adminBase . '/staff'), ENT_QUOTES, 'UTF-8') ?>">Annuler</a>
    </div>
</form>
<script src="/assets/js/catmin-staff.js?v=1"></script>
<?php
$content = (string) ob_get_clean();
require CATMIN_ADMIN . '/views/layouts/admin.php';
