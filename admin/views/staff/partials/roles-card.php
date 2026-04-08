<?php

declare(strict_types=1);
$isSuper = ((string) ($staff['role_slug'] ?? '') === 'super-admin');
?>
<section class="card h-100">
    <div class="card-header bg-transparent border-0 pt-3"><h3 class="h6 mb-0"><?= htmlspecialchars(__('staff.role_permissions.title'), ENT_QUOTES, 'UTF-8') ?></h3></div>
    <div class="card-body pt-2">
        <p class="mb-2"><span class="badge text-bg-info"><?= htmlspecialchars((string) ($staff['role_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span></p>
        <?php if ($isSuper): ?>
            <div class="alert alert-warning py-2 mb-0"><?= htmlspecialchars(__('staff.role_permissions.superadmin_protected'), ENT_QUOTES, 'UTF-8') ?></div>
        <?php else: ?>
            <p class="small text-body-secondary mb-0"><?= htmlspecialchars(__('staff.role_permissions.editable_notice'), ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
    </div>
</section>
