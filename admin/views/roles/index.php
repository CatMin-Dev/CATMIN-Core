<?php

declare(strict_types=1);

$pageTitle = __('roles.title.index');
$pageDescription = __('roles.description.index');
$activeNav = 'roles';
$breadcrumbs = [
    ['label' => 'Admin', 'href' => $adminBase . '/'],
    ['label' => __('nav.roles_permissions')],
];
$pageActions = [];

ob_start();
?>
<section class="card mb-3">
    <div class="card-body py-2 d-flex justify-content-between align-items-center">
        <span class="small text-body-secondary"><?= htmlspecialchars(__('roles.matrix_admin'), ENT_QUOTES, 'UTF-8') ?></span>
        <a class="btn btn-primary btn-sm" href="<?= htmlspecialchars((string) ($adminBase . '/roles/create'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(__('roles.create_role'), ENT_QUOTES, 'UTF-8') ?></a>
    </div>
</section>
<?php require __DIR__ . '/partials/table.php'; ?>
<script src="/assets/js/catmin-roles.js?v=2"></script>
<?php
$content = (string) ob_get_clean();
require CATMIN_ADMIN . '/views/layouts/admin.php';
