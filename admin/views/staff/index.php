<?php

declare(strict_types=1);

$pageTitle = __('staff.title.index');
$pageDescription = __('staff.description.index');
$activeNav = 'staff';
$breadcrumbs = [
    ['label' => 'Admin', 'href' => $adminBase . '/'],
    ['label' => __('nav.staff_admins')],
];
$pageActions = [];

ob_start();
?>
<section class="card">
    <div class="cat-staff-manage-bar">
        <span class="small cat-staff-manage-bar-label"><?= htmlspecialchars(__('staff.manage_accounts'), ENT_QUOTES, 'UTF-8') ?></span>
        <a class="btn btn-primary cat-staff-manage-cta" href="<?= htmlspecialchars((string) ($adminBase . '/staff/create'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(__('staff.add_account'), ENT_QUOTES, 'UTF-8') ?></a>
    </div>
</section>

<?php require __DIR__ . '/partials/filters.php'; ?>
<?php require __DIR__ . '/partials/bulk-bar.php'; ?>
<?php require __DIR__ . '/partials/table.php'; ?>

<section class="d-flex justify-content-between align-items-center mt-2 small text-body-secondary">
    <span><?= htmlspecialchars(__('common.page'), ENT_QUOTES, 'UTF-8') ?> <?= (int) ($pagination['page'] ?? 1) ?> / <?= (int) ($pagination['pages'] ?? 1) ?></span>
    <div class="d-flex gap-2">
        <?php
        $currentPage = (int) ($pagination['page'] ?? 1);
        $maxPages = (int) ($pagination['pages'] ?? 1);
        $queryBase = $filters;
        unset($queryBase['page']);
        ?>
        <a class="btn btn-sm btn-outline-secondary <?= $currentPage <= 1 ? 'disabled' : '' ?>" href="<?= htmlspecialchars((string) ($adminBase . '/staff?' . http_build_query(array_merge($queryBase, ['page' => max(1, $currentPage - 1)]))), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(__('common.previous'), ENT_QUOTES, 'UTF-8') ?></a>
        <a class="btn btn-sm btn-outline-secondary <?= $currentPage >= $maxPages ? 'disabled' : '' ?>" href="<?= htmlspecialchars((string) ($adminBase . '/staff?' . http_build_query(array_merge($queryBase, ['page' => min($maxPages, $currentPage + 1)]))), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(__('common.next'), ENT_QUOTES, 'UTF-8') ?></a>
    </div>
</section>

<script src="/assets/js/catmin-staff.js?v=1"></script>
<?php
$content = (string) ob_get_clean();
require CATMIN_ADMIN . '/views/layouts/admin.php';
