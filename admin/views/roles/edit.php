<?php

declare(strict_types=1);

use Core\security\CsrfManager;

$pageTitle = __('roles.title.edit');
$pageDescription = __('roles.description.edit');
$activeNav = 'roles';
$breadcrumbs = [
    ['label' => 'Admin', 'href' => $adminBase . '/'],
    ['label' => __('nav.roles_permissions'), 'href' => $adminBase . '/roles'],
    ['label' => (string) ($role['name'] ?? __('common.role'))],
];
$pageActions = [
    ['label' => __('roles.action.view_role'), 'href' => $adminBase . '/roles/' . (int) ($role['id'] ?? 0), 'class' => 'btn btn-outline-secondary btn-sm'],
];

ob_start();
?>
<form method="post" action="<?= htmlspecialchars((string) ($adminBase . '/roles/' . (int) ($role['id'] ?? 0) . '/edit'), ENT_QUOTES, 'UTF-8') ?>" class="d-grid gap-3">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars((new CsrfManager())->token(), ENT_QUOTES, 'UTF-8') ?>">
    <?php require __DIR__ . '/partials/form.php'; ?>
    <?php require __DIR__ . '/partials/permissions-matrix.php'; ?>
    <div class="d-flex gap-2">
        <button class="btn btn-primary" type="submit"><?= htmlspecialchars(__('common.save'), ENT_QUOTES, 'UTF-8') ?></button>
        <a class="btn btn-outline-secondary" href="<?= htmlspecialchars((string) ($adminBase . '/roles/' . (int) ($role['id'] ?? 0)), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(__('common.cancel'), ENT_QUOTES, 'UTF-8') ?></a>
    </div>
</form>
<script src="/assets/js/catmin-roles.js?v=2"></script>
<?php
$content = (string) ob_get_clean();
require CATMIN_ADMIN . '/views/layouts/admin.php';
