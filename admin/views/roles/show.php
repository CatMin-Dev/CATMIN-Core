<?php

declare(strict_types=1);

$pageTitle = __('roles.title.show');
$pageDescription = __('roles.description.show');
$activeNav = 'roles';
$breadcrumbs = [
    ['label' => 'Admin', 'href' => $adminBase . '/'],
    ['label' => __('nav.roles_permissions'), 'href' => $adminBase . '/roles'],
    ['label' => (string) ($role['name'] ?? __('common.role'))],
];
$pageActions = [
    ['label' => __('common.edit'), 'href' => $adminBase . '/roles/' . (int) ($role['id'] ?? 0) . '/edit', 'class' => 'btn btn-primary btn-sm'],
];

ob_start();
?>
<section class="row g-3">
    <div class="col-12 col-xl-4"><?php require __DIR__ . '/partials/role-summary.php'; ?></div>
    <div class="col-12 col-xl-8">
        <section class="card h-100">
            <div class="card-header bg-transparent border-0 pt-3"><h3 class="h6 mb-0"><?= htmlspecialchars(__('roles.active_permissions.title'), ENT_QUOTES, 'UTF-8') ?></h3></div>
            <div class="card-body pt-2">
                <?php if (($activePermissions ?? []) === []): ?>
                    <p class="small text-body-secondary mb-0"><?= htmlspecialchars(__('roles.active_permissions.empty'), ENT_QUOTES, 'UTF-8') ?></p>
                <?php else: ?>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($activePermissions as $permission): ?>
                            <span class="badge text-bg-dark"><?= htmlspecialchars((string) ($permission['slug'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</section>

<section class="card">
    <div class="card-header bg-transparent border-0 pt-3"><h3 class="h6 mb-0"><?= htmlspecialchars(__('roles.linked_users.title'), ENT_QUOTES, 'UTF-8') ?></h3></div>
    <div class="card-body pt-2">
        <?php if (($linkedUsers ?? []) === []): ?>
            <p class="small text-body-secondary mb-0"><?= htmlspecialchars(__('roles.linked_users.empty'), ENT_QUOTES, 'UTF-8') ?></p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th><?= htmlspecialchars(__('common.username'), ENT_QUOTES, 'UTF-8') ?></th><th><?= htmlspecialchars(__('common.email'), ENT_QUOTES, 'UTF-8') ?></th><th><?= htmlspecialchars(__('common.status'), ENT_QUOTES, 'UTF-8') ?></th></tr></thead>
                    <tbody>
                    <?php foreach ($linkedUsers as $userRow): ?>
                        <tr>
                            <td><?= htmlspecialchars((string) ($userRow['username'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) ($userRow['email'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= ((int) ($userRow['is_active'] ?? 0) === 1) ? '<span class="badge text-bg-success">' . htmlspecialchars(__('common.active'), ENT_QUOTES, 'UTF-8') . '</span>' : '<span class="badge text-bg-secondary">' . htmlspecialchars(__('common.inactive'), ENT_QUOTES, 'UTF-8') . '</span>' ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php
$content = (string) ob_get_clean();
require CATMIN_ADMIN . '/views/layouts/admin.php';
