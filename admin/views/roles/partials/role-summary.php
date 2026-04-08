<?php

declare(strict_types=1);

$role = isset($role) && is_array($role) ? $role : [];
?>
<section class="card h-100">
    <div class="card-header bg-transparent border-0 pt-3"><h3 class="h6 mb-0">Role Summary</h3></div>
    <div class="card-body pt-2">
        <dl class="row small mb-0">
            <dt class="col-5">Nom</dt><dd class="col-7"><?= htmlspecialchars((string) ($role['name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></dd>
            <dt class="col-5">Slug</dt><dd class="col-7"><code><?= htmlspecialchars((string) ($role['slug'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></code></dd>
            <dt class="col-5">Systeme</dt><dd class="col-7"><?= ((int) ($role['is_system'] ?? 0) === 1) ? 'Oui' : 'Non' ?></dd>
            <dt class="col-5">Creation</dt><dd class="col-7"><?= htmlspecialchars((string) ($role['created_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></dd>
        </dl>
    </div>
</section>
