<?php
$profile = is_array($topbar['profile'] ?? null) ? $topbar['profile'] : [];
$userLabel = (string) ($profile['username'] ?? ($user['username'] ?? $user['email'] ?? 'admin'));
$userRole = (string) ($profile['role'] ?? 'founder');
$profileHref = $adminBase . '/settings/general';
$supportHref = $adminBase . '/system/logs';
$lockHref = $adminBase . '/locked';
if ($userRole === '' || $userRole === 'super-admin') {
    $userRole = __('topbar.role.founder');
}
?>
<div class="dropdown">
    <button type="button" class="cat-user-card dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" aria-label="<?= htmlspecialchars(__('topbar.profile'), ENT_QUOTES, 'UTF-8') ?>">
        <span class="cat-user-avatar"><i class="bi bi-person"></i></span>
        <span class="cat-user-meta">
            <strong><?= htmlspecialchars($userLabel, ENT_QUOTES, 'UTF-8') ?></strong>
            <small><?= htmlspecialchars($userRole, ENT_QUOTES, 'UTF-8') ?></small>
        </span>
    </button>
    <ul class="dropdown-menu dropdown-menu-end cat-theme-menu cat-profile-menu">
        <li><a class="dropdown-item" href="<?= htmlspecialchars($profileHref, ENT_QUOTES, 'UTF-8') ?>"><i class="bi bi-person me-2"></i><?= htmlspecialchars(__('topbar.profile'), ENT_QUOTES, 'UTF-8') ?></a></li>
        <li><a class="dropdown-item" href="<?= htmlspecialchars($supportHref, ENT_QUOTES, 'UTF-8') ?>"><i class="bi bi-life-preserver me-2"></i><?= htmlspecialchars(__('topbar.support'), ENT_QUOTES, 'UTF-8') ?></a></li>
        <li><a class="dropdown-item" href="<?= htmlspecialchars($lockHref, ENT_QUOTES, 'UTF-8') ?>"><i class="bi bi-lock me-2"></i><?= htmlspecialchars(__('topbar.lock_screen'), ENT_QUOTES, 'UTF-8') ?></a></li>
        <li><a class="dropdown-item" href="<?= htmlspecialchars($adminBase . '/logout', ENT_QUOTES, 'UTF-8') ?>"><i class="bi bi-box-arrow-right me-2"></i><?= htmlspecialchars(__('topbar.logout'), ENT_QUOTES, 'UTF-8') ?></a></li>
    </ul>
</div>
