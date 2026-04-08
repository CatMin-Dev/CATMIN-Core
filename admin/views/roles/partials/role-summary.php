<?php

declare(strict_types=1);

$role = isset($role) && is_array($role) ? $role : [];
?>
<section class="card h-100">
    <div class="card-header bg-transparent border-0 pt-3"><h3 class="h6 mb-0"><?= htmlspecialchars(__('roles.summary.title'), ENT_QUOTES, 'UTF-8') ?></h3></div>
    <div class="card-body pt-2">
        <dl class="row small mb-0">
            <dt class="col-5"><?= htmlspecialchars(__('common.name'), ENT_QUOTES, 'UTF-8') ?></dt><dd class="col-7"><?= htmlspecialchars((string) ($role['name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></dd>
            <dt class="col-5"><?= htmlspecialchars(__('common.slug'), ENT_QUOTES, 'UTF-8') ?></dt><dd class="col-7"><code><?= htmlspecialchars((string) ($role['slug'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></code></dd>
            <dt class="col-5"><?= htmlspecialchars(__('roles.summary.system'), ENT_QUOTES, 'UTF-8') ?></dt><dd class="col-7"><?= ((int) ($role['is_system'] ?? 0) === 1) ? htmlspecialchars(__('common.yes'), ENT_QUOTES, 'UTF-8') : htmlspecialchars(__('common.no'), ENT_QUOTES, 'UTF-8') ?></dd>
            <dt class="col-5"><?= htmlspecialchars(__('common.creation'), ENT_QUOTES, 'UTF-8') ?></dt><dd class="col-7"><?= htmlspecialchars((string) ($role['created_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></dd>
        </dl>
    </div>
</section>
