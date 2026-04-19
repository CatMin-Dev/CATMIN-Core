<?php

declare(strict_types=1);

?>
<div class="row g-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h2 class="h5 mb-1"><?= htmlspecialchars(__('module.cat-contract-demo.dashboard.heading'), ENT_QUOTES, 'UTF-8') ?></h2>
                <p class="text-body-secondary mb-0">
                    <?= htmlspecialchars(__('module.cat-contract-demo.dashboard.intro'), ENT_QUOTES, 'UTF-8') ?>
                </p>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <h3 class="h6 mb-2"><i class="bi bi-check-circle-fill text-success me-1"></i> <?= htmlspecialchars(__('module.cat-contract-demo.dashboard.card.admin_route_title'), ENT_QUOTES, 'UTF-8') ?></h3>
                <code class="small">GET /contract-demo</code>
                <p class="text-body-secondary small mt-2 mb-0">
                    <?= htmlspecialchars(__('module.cat-contract-demo.dashboard.card.admin_route_desc', ['permission' => 'example.read']), ENT_QUOTES, 'UTF-8') ?>
                </p>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <h3 class="h6 mb-2"><i class="bi bi-shield-check text-primary me-1"></i> <?= htmlspecialchars(__('module.cat-contract-demo.dashboard.card.auth_title'), ENT_QUOTES, 'UTF-8') ?></h3>
                <span class="badge text-bg-success"><?= htmlspecialchars(__('module.cat-contract-demo.dashboard.card.auth_badge'), ENT_QUOTES, 'UTF-8') ?></span>
                <p class="text-body-secondary small mt-2 mb-0">
                    <?= htmlspecialchars(__('module.cat-contract-demo.dashboard.card.auth_desc'), ENT_QUOTES, 'UTF-8') ?>
                </p>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <h3 class="h6 mb-2"><i class="bi bi-person-lock text-warning me-1"></i> <?= htmlspecialchars(__('module.cat-contract-demo.dashboard.card.permission_title'), ENT_QUOTES, 'UTF-8') ?></h3>
                <code class="small">example.read</code>
                <p class="text-body-secondary small mt-2 mb-0">
                    <?= htmlspecialchars(__('module.cat-contract-demo.dashboard.card.permission_desc'), ENT_QUOTES, 'UTF-8') ?>
                </p>
            </div>
        </div>
    </div>
</div>
