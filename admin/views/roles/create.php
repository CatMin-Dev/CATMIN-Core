<?php

declare(strict_types=1);

use Core\security\CsrfManager;

$pageTitle = __('roles.title.create');
$pageDescription = __('roles.description.create');
$activeNav = 'roles';
$breadcrumbs = [
    ['label' => 'Admin', 'href' => $adminBase . '/'],
    ['label' => __('nav.roles_permissions'), 'href' => $adminBase . '/roles'],
    ['label' => __('common.creation')],
];
$pageActions = [];

ob_start();
?>
<form method="post" action="<?= htmlspecialchars((string) ($adminBase . '/roles/create'), ENT_QUOTES, 'UTF-8') ?>" class="d-grid gap-3">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars((new CsrfManager())->token(), ENT_QUOTES, 'UTF-8') ?>">
    <?php require __DIR__ . '/partials/form.php'; ?>
    <?php require __DIR__ . '/partials/permissions-matrix.php'; ?>
    <div class="d-flex gap-2">
        <button class="btn btn-primary" type="submit"><?= htmlspecialchars(__('common.create'), ENT_QUOTES, 'UTF-8') ?></button>
        <a class="btn btn-outline-secondary" href="<?= htmlspecialchars((string) ($adminBase . '/roles'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(__('common.cancel'), ENT_QUOTES, 'UTF-8') ?></a>
    </div>
</form>
<script src="/assets/js/catmin-roles.js?v=3"></script>
<?php
$content = (string) ob_get_clean();
require CATMIN_ADMIN . '/views/layouts/admin.php';
