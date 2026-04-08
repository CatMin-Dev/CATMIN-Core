<?php

declare(strict_types=1);

use Core\security\CsrfManager;

$pageTitle = 'Editer role';
$pageDescription = 'Modification du role et de sa matrice de permissions.';
$activeNav = 'roles';
$breadcrumbs = [
    ['label' => 'Admin', 'href' => $adminBase . '/'],
    ['label' => 'Roles & Permissions', 'href' => $adminBase . '/roles'],
    ['label' => (string) ($role['name'] ?? 'Role')],
];
$pageActions = [
    ['label' => 'Voir role', 'href' => $adminBase . '/roles/' . (int) ($role['id'] ?? 0), 'class' => 'btn btn-outline-secondary btn-sm'],
];

ob_start();
?>
<form method="post" action="<?= htmlspecialchars((string) ($adminBase . '/roles/' . (int) ($role['id'] ?? 0) . '/edit'), ENT_QUOTES, 'UTF-8') ?>" class="d-grid gap-3">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars((new CsrfManager())->token(), ENT_QUOTES, 'UTF-8') ?>">
    <?php require __DIR__ . '/partials/form.php'; ?>
    <?php require __DIR__ . '/partials/permissions-matrix.php'; ?>
    <div class="d-flex gap-2">
        <button class="btn btn-primary" type="submit">Enregistrer</button>
        <a class="btn btn-outline-secondary" href="<?= htmlspecialchars((string) ($adminBase . '/roles/' . (int) ($role['id'] ?? 0)), ENT_QUOTES, 'UTF-8') ?>">Annuler</a>
    </div>
</form>
<script src="/assets/js/catmin-roles.js?v=2"></script>
<?php
$content = (string) ob_get_clean();
require CATMIN_ADMIN . '/views/layouts/admin.php';
