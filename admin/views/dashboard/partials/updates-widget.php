<?php

declare(strict_types=1);

$updatesSnapshot = is_array($updatesSnapshot ?? null) ? $updatesSnapshot : [];
$updatesSummary = (array) ($updatesSnapshot['summary'] ?? []);
$coreUpdateAvailable = (bool) ($updatesSummary['core_update_available'] ?? false);
$modulesWithUpdates = (int) ($updatesSummary['modules_with_updates'] ?? 0);
$trustAlerts = (int) ($updatesSummary['trust_alerts'] ?? 0);

$toneClass = 'text-bg-success';
if ($trustAlerts > 0) {
    $toneClass = 'text-bg-danger';
} elseif ($coreUpdateAvailable || $modulesWithUpdates > 0) {
    $toneClass = 'text-bg-warning';
}
?>
<section class="card mb-3">
    <div class="card-header bg-transparent border-0 pt-3 d-flex justify-content-between align-items-center">
        <h3 class="h6 mb-0"><?= htmlspecialchars(__('updates.title'), ENT_QUOTES, 'UTF-8') ?></h3>
        <a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars($adminBase . '/system/updates', ENT_QUOTES, 'UTF-8') ?>">
            <?= htmlspecialchars(__('updates.action.open_center'), ENT_QUOTES, 'UTF-8') ?>
        </a>
    </div>
    <div class="card-body pt-2">
        <div class="row g-2">
            <div class="col-12 col-lg-4">
                <div class="border rounded p-2 small">
                    <span class="text-body-secondary d-block"><?= htmlspecialchars(__('updates.summary.core'), ENT_QUOTES, 'UTF-8') ?></span>
                    <strong><?= htmlspecialchars($coreUpdateAvailable ? __('updates.status.core_available') : __('updates.status.core_uptodate'), ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
            </div>
            <div class="col-12 col-lg-4">
                <div class="border rounded p-2 small">
                    <span class="text-body-secondary d-block"><?= htmlspecialchars(__('updates.summary.modules'), ENT_QUOTES, 'UTF-8') ?></span>
                    <strong><?= $modulesWithUpdates ?></strong>
                </div>
            </div>
            <div class="col-12 col-lg-4">
                <div class="border rounded p-2 small">
                    <span class="text-body-secondary d-block"><?= htmlspecialchars(__('updates.summary.alerts'), ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="badge <?= $toneClass ?>"><?= $trustAlerts ?></span>
                </div>
            </div>
        </div>
    </div>
</section>

