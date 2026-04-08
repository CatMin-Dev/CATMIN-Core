<?php
$profile = is_array($topbar['profile'] ?? null) ? $topbar['profile'] : [];
$userLabel = (string) ($profile['username'] ?? ($user['username'] ?? $user['email'] ?? 'admin'));
$userRole = (string) ($profile['role'] ?? 'founder');
$userId = (int) ($user['id'] ?? 0);
$profileHref = $userId > 0
    ? ($adminBase . '/staff/show?id=' . rawurlencode((string) $userId))
    : ($adminBase . '/settings/general');
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
    <ul class="dropdown-menu dropdown-menu-end">
        <li><a class="dropdown-item" href="<?= htmlspecialchars($profileHref, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(__('topbar.profile'), ENT_QUOTES, 'UTF-8') ?></a></li>
        <li><a class="dropdown-item" href="<?= htmlspecialchars($adminBase . '/password/change', ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(__('topbar.security'), ENT_QUOTES, 'UTF-8') ?></a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item" href="<?= htmlspecialchars($adminBase . '/logout', ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(__('topbar.logout'), ENT_QUOTES, 'UTF-8') ?></a></li>
    </ul>
</div>
