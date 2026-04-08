<?php

declare(strict_types=1);

use Core\security\CsrfManager;

$pageTitle = __('staff.title.create');
$pageDescription = __('staff.description.create');
$activeNav = 'staff';
$breadcrumbs = [
    ['label' => 'Admin', 'href' => $adminBase . '/'],
    ['label' => __('nav.staff_admins'), 'href' => $adminBase . '/staff'],
    ['label' => __('common.creation')],
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
            <h3 class="h6"><?= htmlspecialchars(__('staff.security_rules.title'), ENT_QUOTES, 'UTF-8') ?></h3>
            <ul class="small text-body-secondary mb-0">
                <li><?= htmlspecialchars(__('staff.security_rules.password'), ENT_QUOTES, 'UTF-8') ?></li>
                <li><?= htmlspecialchars(__('staff.security_rules.role'), ENT_QUOTES, 'UTF-8') ?></li>
                <li><?= htmlspecialchars(__('staff.security_rules.superadmin'), ENT_QUOTES, 'UTF-8') ?></li>
            </ul>
        </div></div>
    </div>
    <div class="col-12 d-flex gap-2">
        <button class="btn btn-primary" type="submit"><?= htmlspecialchars(__('common.create'), ENT_QUOTES, 'UTF-8') ?></button>
        <a class="btn btn-outline-secondary" href="<?= htmlspecialchars((string) ($adminBase . '/staff'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(__('common.cancel'), ENT_QUOTES, 'UTF-8') ?></a>
    </div>
</form>
<script src="/assets/js/catmin-staff.js?v=1"></script>
<?php
$content = (string) ob_get_clean();
require CATMIN_ADMIN . '/views/layouts/admin.php';
