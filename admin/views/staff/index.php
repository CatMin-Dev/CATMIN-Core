<?php

declare(strict_types=1);

$pageTitle = 'Staff / Administrateurs';
$pageDescription = 'Listing complet des comptes admin/staff avec filtres, tri et actions bulk.';
$activeNav = 'staff';
$breadcrumbs = [
    ['label' => 'Admin', 'href' => $adminBase . '/'],
    ['label' => 'Staff / Admins'],
];
$pageActions = [];

ob_start();
?>
<section class="card mb-3">
    <div class="card-body py-2 d-flex justify-content-between align-items-center">
        <span class="small text-body-secondary">Gestion des comptes administrateurs</span>
        <a class="btn btn-primary btn-sm" href="<?= htmlspecialchars((string) ($adminBase . '/staff/create'), ENT_QUOTES, 'UTF-8') ?>">Ajouter un compte</a>
    </div>
</section>

<?php require __DIR__ . '/partials/filters.php'; ?>
<?php require __DIR__ . '/partials/bulk-bar.php'; ?>
<?php require __DIR__ . '/partials/table.php'; ?>

<section class="d-flex justify-content-between align-items-center mt-2 small text-body-secondary">
    <span>Page <?= (int) ($pagination['page'] ?? 1) ?> / <?= (int) ($pagination['pages'] ?? 1) ?></span>
    <div class="d-flex gap-2">
        <?php
        $currentPage = (int) ($pagination['page'] ?? 1);
        $maxPages = (int) ($pagination['pages'] ?? 1);
        $queryBase = $filters;
        unset($queryBase['page']);
        ?>
        <a class="btn btn-sm btn-outline-secondary <?= $currentPage <= 1 ? 'disabled' : '' ?>" href="<?= htmlspecialchars((string) ($adminBase . '/staff?' . http_build_query(array_merge($queryBase, ['page' => max(1, $currentPage - 1)]))), ENT_QUOTES, 'UTF-8') ?>">Precedent</a>
        <a class="btn btn-sm btn-outline-secondary <?= $currentPage >= $maxPages ? 'disabled' : '' ?>" href="<?= htmlspecialchars((string) ($adminBase . '/staff?' . http_build_query(array_merge($queryBase, ['page' => min($maxPages, $currentPage + 1)]))), ENT_QUOTES, 'UTF-8') ?>">Suivant</a>
    </div>
</section>

<script src="/assets/js/catmin-staff.js?v=1"></script>
<?php
$content = (string) ob_get_clean();
require CATMIN_ADMIN . '/views/layouts/admin.php';
