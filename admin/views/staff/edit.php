<?php

declare(strict_types=1);

use Core\security\CsrfManager;

$pageTitle = __('staff.title.edit');
$pageDescription = __('staff.description.edit');
$activeNav = 'staff';
$breadcrumbs = [
    ['label' => 'Admin', 'href' => $adminBase . '/'],
    ['label' => __('nav.staff_admins'), 'href' => $adminBase . '/staff'],
    ['label' => (string) ($staff['username'] ?? __('staff.account'))],
];
$pageActions = [
    ['label' => __('staff.action.view_profile'), 'href' => $adminBase . '/staff/' . (int) ($staff['id'] ?? 0), 'class' => 'btn btn-outline-secondary btn-sm'],
];

$isSuperAdmin = ((string) ($staff['role_slug'] ?? '') === 'super-admin');

ob_start();
?>
<?php if ($isSuperAdmin): ?>
    <div class="alert alert-warning"><?= htmlspecialchars(__('staff.superadmin.protected_notice'), ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<form method="post" action="<?= htmlspecialchars((string) ($adminBase . '/staff/' . (int) ($staff['id'] ?? 0) . '/edit'), ENT_QUOTES, 'UTF-8') ?>" class="row g-3">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars((new CsrfManager())->token(), ENT_QUOTES, 'UTF-8') ?>">
    <div class="col-12 col-xl-8">
        <?php $isEdit = true; require __DIR__ . '/partials/form.php'; ?>
    </div>
    <div class="col-12 col-xl-4 d-grid gap-3">
        <div class="card"><div class="card-body small">
            <h3 class="h6"><?= htmlspecialchars(__('staff.meta.title'), ENT_QUOTES, 'UTF-8') ?></h3>
            <p class="mb-1"><?= htmlspecialchars(__('staff.meta.last_login'), ENT_QUOTES, 'UTF-8') ?>: <?= htmlspecialchars((string) ($staff['last_login_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
            <p class="mb-0"><?= htmlspecialchars(__('staff.meta.created_at'), ENT_QUOTES, 'UTF-8') ?>: <?= htmlspecialchars((string) ($staff['created_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
        </div></div>
        <div class="card"><div class="card-body small">
            <h3 class="h6"><?= htmlspecialchars(__('staff.security.title'), ENT_QUOTES, 'UTF-8') ?></h3>
            <p class="mb-0"><?= htmlspecialchars(__('staff.security.reauth_notice'), ENT_QUOTES, 'UTF-8') ?></p>
        </div></div>
    </div>
    <div class="col-12 d-flex gap-2">
        <button class="btn btn-primary" type="submit"><?= htmlspecialchars(__('common.save'), ENT_QUOTES, 'UTF-8') ?></button>
        <a class="btn btn-outline-secondary" href="<?= htmlspecialchars((string) ($adminBase . '/staff/' . (int) ($staff['id'] ?? 0)), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(__('common.cancel'), ENT_QUOTES, 'UTF-8') ?></a>
    </div>
</form>
<script src="/assets/js/catmin-staff.js?v=1"></script>
<?php
$content = (string) ob_get_clean();
require CATMIN_ADMIN . '/views/layouts/admin.php';
