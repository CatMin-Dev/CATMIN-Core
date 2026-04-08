<?php

declare(strict_types=1);

$widgets = (array) (($monitoring['widgets'] ?? []));
?>
<section class="card mb-3">
    <div class="card-header bg-transparent border-0 pt-3 d-flex justify-content-between align-items-center">
        <h3 class="h6 mb-0">Monitoring rapide</h3>
        <a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars($adminBase . '/system/monitoring', ENT_QUOTES, 'UTF-8') ?>">Ouvrir monitoring</a>
    </div>
    <div class="card-body pt-2">
        <div class="row g-2">
            <div class="col-6 col-lg-3"><div class="border rounded p-2 small">Erreurs critiques: <strong><?= (int) ($widgets['critical_errors']['count'] ?? 0) ?></strong></div></div>
            <div class="col-6 col-lg-3"><div class="border rounded p-2 small">Alertes sécurité: <strong><?= (int) ($widgets['security_alerts']['count'] ?? 0) ?></strong></div></div>
            <div class="col-6 col-lg-3"><div class="border rounded p-2 small">Maintenance: <strong><?= !empty($widgets['maintenance']['active']) ? 'ON' : 'OFF' ?></strong></div></div>
            <div class="col-6 col-lg-3"><div class="border rounded p-2 small">Modules à vérifier: <strong><?= (int) ($widgets['module_issues']['count'] ?? 0) ?></strong></div></div>
        </div>
    </div>
</section>

