<?php
$notifications = is_array($topbar['notifications'] ?? null) ? $topbar['notifications'] : [];
$recentNotifications = is_array($notifications['recent'] ?? null) ? $notifications['recent'] : [];
$unreadCount = (int) ($notifications['unread'] ?? 0);
$next = (string) ($_SERVER['REQUEST_URI'] ?? ($adminBase . '/'));
if (!str_starts_with($next, $adminBase)) {
    $next = $adminBase . '/';
}
?>
<div class="dropdown">
    <button type="button" class="cat-icon-btn has-dot dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" aria-label="<?= htmlspecialchars(__('topbar.notifications'), ENT_QUOTES, 'UTF-8') ?>">
        <i class="bi bi-bell"></i>
        <?php if ($unreadCount > 0): ?>
            <span class="cat-topbar-alert-count"><?= $unreadCount > 99 ? '99+' : $unreadCount ?></span>
        <?php endif; ?>
    </button>
    <div class="dropdown-menu dropdown-menu-end cat-topbar-dropdown cat-topbar-dropdown-notifs">
        <div class="cat-topbar-dropdown-head d-flex align-items-center justify-content-between">
            <strong><?= htmlspecialchars(__('topbar.notifications'), ENT_QUOTES, 'UTF-8') ?></strong>
            <?php if ($unreadCount > 0): ?>
                <a class="small text-decoration-none" href="<?= htmlspecialchars($adminBase . '/notifications/mark-all-read?next=' . rawurlencode($next), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(__('topbar.notifications_mark_all'), ENT_QUOTES, 'UTF-8') ?></a>
            <?php endif; ?>
        </div>
        <div class="cat-topbar-dropdown-body">
            <?php if ($recentNotifications === []): ?>
                <p class="small text-body-secondary mb-0 px-2 py-2"><?= htmlspecialchars(__('topbar.notifications_empty'), ENT_QUOTES, 'UTF-8') ?></p>
            <?php else: ?>
                <?php foreach ($recentNotifications as $notification): ?>
                    <?php
                    $isRead = !empty($notification['is_read']);
                    $actionUrl = trim((string) ($notification['action_url'] ?? ''));
                    $title = (string) ($notification['title'] ?? 'Notification');
                    $message = (string) ($notification['message'] ?? '');
                    $href = $actionUrl !== ''
                        ? $actionUrl
                        : ($adminBase . '/notifications/read/' . (int) ($notification['id'] ?? 0) . '?next=' . rawurlencode($next));
                    ?>
                    <a class="cat-notification-item <?= $isRead ? 'is-read' : 'is-unread' ?>" href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>">
                        <div class="cat-notification-item-title"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></div>
                        <?php if ($message !== ''): ?><div class="cat-notification-item-message"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="cat-topbar-dropdown-foot">
            <a class="small text-decoration-none" href="<?= htmlspecialchars($adminBase . '/notifications', ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(__('topbar.notifications_view_all'), ENT_QUOTES, 'UTF-8') ?></a>
        </div>
    </div>
</div>
