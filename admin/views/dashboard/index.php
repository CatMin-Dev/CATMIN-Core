<?php

declare(strict_types=1);

$pageTitle = 'Dashboard';
$pageDescription = 'Vue d\'ensemble administration CATMIN.';
$activeNav = 'dashboard';
$breadcrumbs = [
    ['label' => 'Admin', 'href' => $adminBase . '/'],
    ['label' => 'Dashboard'],
];
$pageActions = [];

ob_start();
?>
<?php require __DIR__ . '/partials/stats.php'; ?>

<section class="row g-3">
    <div class="col-12 col-xl-7"><?php require __DIR__ . '/partials/activity.php'; ?></div>
    <div class="col-12 col-xl-5"><?php require __DIR__ . '/partials/system.php'; ?></div>
</section>

<section class="row g-3">
    <div class="col-12 col-xl-4"><?php require __DIR__ . '/partials/quick-actions.php'; ?></div>
    <div class="col-12 col-xl-8"><?php require __DIR__ . '/partials/recent-events.php'; ?></div>
</section>

<?php require __DIR__ . '/partials/version.php'; ?>

<script src="/assets/js/catmin-dashboard.js?v=2"></script>
<?php
$content = (string) ob_get_clean();

require CATMIN_ADMIN . '/views/layouts/admin.php';
