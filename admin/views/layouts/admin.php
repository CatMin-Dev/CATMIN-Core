<?php

declare(strict_types=1);

$pageTitle = isset($pageTitle) ? (string) $pageTitle : 'Administration';
$pageDescription = isset($pageDescription) ? (string) $pageDescription : '';
$pageActions = isset($pageActions) && is_array($pageActions) ? $pageActions : [];
$breadcrumbs = isset($breadcrumbs) && is_array($breadcrumbs) ? $breadcrumbs : [];
$content = isset($content) ? (string) $content : '';
$adminBase = isset($adminBase) ? (string) $adminBase : '/admin';
$activeNav = isset($activeNav) ? (string) $activeNav : 'dashboard';
$user = isset($user) && is_array($user) ? $user : [];
$layoutState = isset($layoutState) && is_array($layoutState) ? $layoutState : [];
?>
<!doctype html>
<html lang="fr" data-bs-theme="corporate">
<?php require __DIR__ . '/partials/head.php'; ?>
<body class="cat-admin-body <?= !empty($layoutState['sidebar_compact']) ? 'cat-sidebar-compact' : 'cat-sidebar-expanded' ?>">
<div class="cat-admin-shell">
    <?php require __DIR__ . '/partials/sidebar.php'; ?>
    <div class="cat-main-shell">
        <?php require __DIR__ . '/partials/topbar.php'; ?>
        <div class="cat-content-shell">
            <?php require __DIR__ . '/partials/page-header.php'; ?>
            <main class="cat-page-content">
                <?= $content ?>
            </main>
            <?php require __DIR__ . '/partials/footer.php'; ?>
        </div>
    </div>
</div>
<?php require __DIR__ . '/partials/notifications-panel.php'; ?>
<?php require __DIR__ . '/partials/quick-actions.php'; ?>
<?php require __DIR__ . '/partials/overlays.php'; ?>
<?php require __DIR__ . '/partials/scripts.php'; ?>
</body>
</html>
