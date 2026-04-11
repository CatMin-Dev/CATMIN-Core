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
$toastMessage = trim((string) ($message ?? ''));
$toastType = trim((string) ($messageType ?? 'success'));
$locale = function_exists('catmin_locale') ? catmin_locale() : 'fr';

if ($locale !== 'fr') {
    if (!class_exists('CoreUiTranslator')) {
        require_once CATMIN_CORE . '/ui-translator.php';
    }
    $translator = new CoreUiTranslator();
    $pageTitle = $translator->translate($pageTitle, $locale);
    $pageDescription = $translator->translate($pageDescription, $locale);
    $toastMessage = $translator->translate($toastMessage, $locale);
    $content = $translator->translate($content, $locale);

    foreach ($pageActions as $i => $action) {
        if (!is_array($action)) {
            continue;
        }
        $action['label'] = $translator->translate((string) ($action['label'] ?? ''), $locale);
        $pageActions[$i] = $action;
    }

    foreach ($breadcrumbs as $i => $crumb) {
        if (!is_array($crumb)) {
            continue;
        }
        $crumb['label'] = $translator->translate((string) ($crumb['label'] ?? ''), $locale);
        $breadcrumbs[$i] = $crumb;
    }
}
?>
<!doctype html>
<html lang="<?= htmlspecialchars($locale, ENT_QUOTES, 'UTF-8') ?>" data-bs-theme="corporate">
<?php require __DIR__ . '/partials/head.php'; ?>
<body
    class="cat-admin-body <?= !empty($layoutState['sidebar_compact']) ? 'cat-sidebar-compact' : 'cat-sidebar-expanded' ?>"
    data-cat-sidebar-server="<?= !empty($layoutState['sidebar_compact']) ? '1' : '0' ?>"
>
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
<?php require __DIR__ . '/partials/toasts.php'; ?>
<?php require __DIR__ . '/partials/notifications-panel.php'; ?>
<?php require __DIR__ . '/partials/quick-actions.php'; ?>
<?php require __DIR__ . '/partials/overlays.php'; ?>
<?php require __DIR__ . '/partials/scripts.php'; ?>
</body>
</html>
