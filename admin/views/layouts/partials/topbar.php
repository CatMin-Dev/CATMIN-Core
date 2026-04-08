<?php
require_once CATMIN_CORE . '/topbar-bridge.php';
$topbar = (new CoreTopbarBridge())->payload(is_array($user ?? null) ? $user : []);
?>
<header class="cat-topbar border-bottom">
    <div class="cat-topbar-left">
        <button type="button" class="cat-icon-btn" data-cat-sidebar-toggle aria-label="Menu">
            <i class="bi bi-list"></i>
        </button>
        <?php require __DIR__ . '/topbar-search.php'; ?>
    </div>

    <div class="cat-topbar-right">
        <?php require __DIR__ . '/topbar-language.php'; ?>
        <?php require __DIR__ . '/topbar-notifications.php'; ?>
        <?php require __DIR__ . '/topbar-apps.php'; ?>
        <?php require __DIR__ . '/topbar-settings.php'; ?>
        <?php require __DIR__ . '/topbar-theme.php'; ?>
        <?php require __DIR__ . '/topbar-fullscreen.php'; ?>
        <?php require __DIR__ . '/topbar-profile.php'; ?>
    </div>
</header>
