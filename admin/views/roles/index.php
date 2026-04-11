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
<section class="card">
    <div class="cat-staff-manage-bar">
        <span class="small cat-staff-manage-bar-label"><?= htmlspecialchars(__('roles.matrix_admin'), ENT_QUOTES, 'UTF-8') ?></span>
        <a class="btn btn-primary cat-staff-manage-cta" href="<?= htmlspecialchars((string) ($adminBase . '/roles/create'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(__('roles.create_role'), ENT_QUOTES, 'UTF-8') ?></a>
    </div>
</section>
<?php require __DIR__ . '/partials/table.php'; ?>
<script src="/assets/js/catmin-roles.js?v=3"></script>
<?php
$content = (string) ob_get_clean();
require CATMIN_ADMIN . '/views/layouts/admin.php';
