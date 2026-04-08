<?php

declare(strict_types=1);

$pageTitle = 'Staff / Administrateurs';
$pageDescription = 'Listing complet des comptes admin/staff avec filtres, tri et actions bulk.';
$activeNav = 'staff';
$breadcrumbs = [
    ['label' => 'Admin', 'href' => $adminBase . '/'],
    ['label' => 'Staff / Admins'],
];
$pageActions = [
    ['label' => 'Ajouter un compte', 'href' => $adminBase . '/staff/create', 'class' => 'btn btn-primary btn-sm'],
];

ob_start();
?>
<?php if (!empty($message)): ?>
    <?php
    $messageText = (string) $message;
    $message = $messageText;
    $type = (string) ($messageType ?? 'success');
    $dismissible = true;
    require CATMIN_ADMIN . '/views/components/alerts/inline.php';
    ?>
<?php endif; ?>

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
