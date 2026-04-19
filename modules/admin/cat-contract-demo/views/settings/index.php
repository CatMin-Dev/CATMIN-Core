<?php

declare(strict_types=1);

$settingsSections = [
    'general' => __('settings.section.general'),
    'appearance' => __('settings.section.appearance'),
    'sidebar' => __('settings.section.sidebar'),
    'mail' => __('settings.section.mail'),
    'performance' => __('settings.section.performance'),
    'security' => __('settings.section.security'),
    'advanced' => __('settings.section.advanced'),
];

?>
<div class="row g-4">
    <div class="col-12 col-lg-3">
        <?php
        $sections = $settingsSections;
        $activeSection = '';
        $settingsModuleLinks = [
            [
                'href' => $adminBase . '/settings/contract-demo',
                'label' => __('module.cat-contract-demo.settings.module_link_label'),
            ],
        ];
        $activeModuleHref = $adminBase . '/settings/contract-demo';
        require CATMIN_ADMIN . '/views/settings/partials/settings-nav.php';
        ?>
    </div>

    <div class="col-12 col-lg-9">
        <div class="card mb-4">
            <div class="card-body">
                <h2 class="h5 mb-1"><?= htmlspecialchars(__('module.cat-contract-demo.settings.heading'), ENT_QUOTES, 'UTF-8') ?></h2>
                <p class="text-body-secondary mb-0">
                    <?= htmlspecialchars(__('module.cat-contract-demo.settings.intro', ['permission' => 'example.settings']), ENT_QUOTES, 'UTF-8') ?>
                </p>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-12 col-md-6">
                <div class="card h-100">
                    <div class="card-body">
                        <h3 class="h6 mb-2"><i class="bi bi-sliders text-primary me-1"></i> <?= htmlspecialchars(__('module.cat-contract-demo.settings.card.route_title'), ENT_QUOTES, 'UTF-8') ?></h3>
                        <code class="small">GET /settings/contract-demo</code>
                        <p class="text-body-secondary small mt-2 mb-0">
                            <?= htmlspecialchars(__('module.cat-contract-demo.settings.card.route_desc'), ENT_QUOTES, 'UTF-8') ?>
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6">
                <div class="card h-100">
                    <div class="card-body">
                        <h3 class="h6 mb-2"><i class="bi bi-person-lock text-warning me-1"></i> <?= htmlspecialchars(__('module.cat-contract-demo.settings.card.permission_title'), ENT_QUOTES, 'UTF-8') ?></h3>
                        <code class="small">example.settings</code>
                        <p class="text-body-secondary small mt-2 mb-0">
                            <?= htmlspecialchars(__('module.cat-contract-demo.settings.card.permission_desc', ['permission' => 'example.settings']), ENT_QUOTES, 'UTF-8') ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
