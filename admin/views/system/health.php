<?php

declare(strict_types=1);

$pageTitle = __('system.health.title');
$pageDescription = '';
$activeNav = 'health';
$breadcrumbs = [
    ['label' => __('common.admin'), 'href' => $adminBase . '/'],
    ['label' => __('nav.system'), 'href' => $adminBase . '/system/monitoring'],
    ['label' => __('system.health.check')],
];

$snapshot = is_array($snapshot ?? null) ? $snapshot : [];
$summary = (array) ($snapshot['summary'] ?? []);
$checks = (array) ($snapshot['checks'] ?? []);
$global = (string) ($snapshot['global'] ?? 'unknown');

ob_start();
?>
<section class="card mb-3">
    <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <h3 class="h6 mb-1"><?= htmlspecialchars(__('system.health.global_status'), ENT_QUOTES, 'UTF-8') ?></h3>
            <p class="mb-0 text-body-secondary"><?= htmlspecialchars(__('system.health.global_help'), ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <span class="badge <?= $global === 'critical' ? 'text-bg-danger' : ($global === 'warning' ? 'text-bg-warning' : ($global === 'healthy' ? 'text-bg-success' : 'text-bg-secondary')) ?>">
            <?= htmlspecialchars(strtoupper($global), ENT_QUOTES, 'UTF-8') ?>
        </span>
    </div>
    <div class="card-body pt-0">
        <div class="row g-2">
            <div class="col-6 col-lg-3"><div class="border rounded p-2 small"><?= htmlspecialchars(__('common.healthy'), ENT_QUOTES, 'UTF-8') ?>: <strong><?= (int) ($summary['healthy'] ?? 0) ?></strong></div></div>
            <div class="col-6 col-lg-3"><div class="border rounded p-2 small"><?= htmlspecialchars(__('common.warning'), ENT_QUOTES, 'UTF-8') ?>: <strong><?= (int) ($summary['warning'] ?? 0) ?></strong></div></div>
            <div class="col-6 col-lg-3"><div class="border rounded p-2 small"><?= htmlspecialchars(__('common.critical'), ENT_QUOTES, 'UTF-8') ?>: <strong><?= (int) ($summary['critical'] ?? 0) ?></strong></div></div>
            <div class="col-6 col-lg-3"><div class="border rounded p-2 small"><?= htmlspecialchars(__('common.unknown'), ENT_QUOTES, 'UTF-8') ?>: <strong><?= (int) ($summary['unknown'] ?? 0) ?></strong></div></div>
        </div>
    </div>
</section>

<section class="card">
    <div class="card-header bg-transparent border-0 pt-3">
        <h3 class="h6 mb-0"><?= htmlspecialchars(__('system.health.detailed_checks'), ENT_QUOTES, 'UTF-8') ?></h3>
    </div>
    <div class="card-body pt-2">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead><tr><th><?= htmlspecialchars(__('system.health.table.check'), ENT_QUOTES, 'UTF-8') ?></th><th><?= htmlspecialchars(__('common.status'), ENT_QUOTES, 'UTF-8') ?></th><th><?= htmlspecialchars(__('system.health.table.detail'), ENT_QUOTES, 'UTF-8') ?></th></tr></thead>
                <tbody>
                <?php foreach ($checks as $check): ?>
                    <?php $checkStatus = (string) ($check['status'] ?? 'unknown'); ?>
                    <tr>
                        <td><?= htmlspecialchars((string) ($check['label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <span class="badge <?= $checkStatus === 'critical' ? 'text-bg-danger' : ($checkStatus === 'warning' ? 'text-bg-warning' : ($checkStatus === 'healthy' ? 'text-bg-success' : 'text-bg-secondary')) ?>">
                                <?= htmlspecialchars($checkStatus, ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </td>
                        <td class="text-body-secondary"><?= htmlspecialchars((string) ($check['detail'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
<?php
$content = (string) ob_get_clean();
require CATMIN_ADMIN . '/views/layouts/admin.php';
