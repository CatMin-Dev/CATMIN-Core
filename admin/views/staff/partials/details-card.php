<?php

declare(strict_types=1);
?>
<section class="card h-100">
    <div class="card-header bg-transparent border-0 pt-3"><h3 class="h6 mb-0"><?= htmlspecialchars(__('staff.details.title'), ENT_QUOTES, 'UTF-8') ?></h3></div>
    <div class="card-body pt-2">
        <dl class="row small mb-0">
            <dt class="col-5">ID</dt><dd class="col-7"><?= (int) ($staff['id'] ?? 0) ?></dd>
            <dt class="col-5"><?= htmlspecialchars(__('common.username'), ENT_QUOTES, 'UTF-8') ?></dt><dd class="col-7"><?= htmlspecialchars((string) ($staff['username'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></dd>
            <dt class="col-5"><?= htmlspecialchars(__('common.email'), ENT_QUOTES, 'UTF-8') ?></dt><dd class="col-7"><?= htmlspecialchars((string) ($staff['email'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></dd>
            <dt class="col-5"><?= htmlspecialchars(__('common.creation'), ENT_QUOTES, 'UTF-8') ?></dt><dd class="col-7"><?= htmlspecialchars((string) ($staff['created_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></dd>
            <dt class="col-5"><?= htmlspecialchars(__('common.updated_at'), ENT_QUOTES, 'UTF-8') ?></dt><dd class="col-7"><?= htmlspecialchars((string) ($staff['updated_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></dd>
        </dl>
    </div>
</section>
