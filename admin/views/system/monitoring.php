<?php

declare(strict_types=1);

$pageTitle = 'Monitoring';
$pageDescription = '';
$activeNav = 'monitoring';
$breadcrumbs = [
    ['label' => 'Admin', 'href' => $adminBase . '/'],
    ['label' => 'Système'],
    ['label' => 'Monitoring'],
];

$snapshot = is_array($snapshot ?? null) ? $snapshot : [];
$widgets = (array) ($snapshot['widgets'] ?? []);
$health = (array) ($snapshot['health'] ?? []);
$healthSummary = (array) ($health['summary'] ?? []);

ob_start();
?>
<section class="row g-3 mb-3">
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card h-100"><div class="card-body">
            <h3 class="h6 mb-1">Erreurs critiques</h3>
            <div class="display-6 mb-2"><?= (int) (($widgets['critical_errors']['count'] ?? 0)) ?></div>
            <p class="small text-body-secondary mb-0"><?= htmlspecialchars((string) (($widgets['critical_errors']['last'] ?? '-') ?: '-'), ENT_QUOTES, 'UTF-8') ?></p>
        </div></div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card h-100"><div class="card-body">
            <h3 class="h6 mb-1">Alertes sécurité</h3>
            <div class="display-6 mb-2"><?= (int) (($widgets['security_alerts']['count'] ?? 0)) ?></div>
            <p class="small text-body-secondary mb-0"><?= htmlspecialchars((string) (($widgets['security_alerts']['last'] ?? '-') ?: '-'), ENT_QUOTES, 'UTF-8') ?></p>
        </div></div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card h-100"><div class="card-body">
            <h3 class="h6 mb-1">Maintenance</h3>
            <div class="display-6 mb-2"><?= !empty($widgets['maintenance']['active']) ? 'ON' : 'OFF' ?></div>
            <p class="small text-body-secondary mb-0"><?= htmlspecialchars((string) (($widgets['maintenance']['meta'] ?? '-') ?: '-'), ENT_QUOTES, 'UTF-8') ?></p>
        </div></div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card h-100"><div class="card-body">
            <h3 class="h6 mb-1">Modules à vérifier</h3>
            <div class="display-6 mb-2"><?= (int) (($widgets['module_issues']['count'] ?? 0)) ?></div>
            <p class="small text-body-secondary mb-0">Modules invalides/incompatibles</p>
        </div></div>
    </div>
</section>

<section class="card">
    <div class="card-header bg-transparent border-0 pt-3 d-flex justify-content-between">
        <h3 class="h6 mb-0">Santé système</h3>
        <a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars($adminBase . '/system/health', ENT_QUOTES, 'UTF-8') ?>">Voir health check</a>
    </div>
    <div class="card-body pt-2">
        <div class="row g-2">
            <div class="col-6 col-lg-3"><div class="border rounded p-2 small">Healthy: <strong><?= (int) ($healthSummary['healthy'] ?? 0) ?></strong></div></div>
            <div class="col-6 col-lg-3"><div class="border rounded p-2 small">Warning: <strong><?= (int) ($healthSummary['warning'] ?? 0) ?></strong></div></div>
            <div class="col-6 col-lg-3"><div class="border rounded p-2 small">Critical: <strong><?= (int) ($healthSummary['critical'] ?? 0) ?></strong></div></div>
            <div class="col-6 col-lg-3"><div class="border rounded p-2 small">Unknown: <strong><?= (int) ($healthSummary['unknown'] ?? 0) ?></strong></div></div>
        </div>
    </div>
</section>
<?php
$content = (string) ob_get_clean();
require CATMIN_ADMIN . '/views/layouts/admin.php';

