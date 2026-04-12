<?php

declare(strict_types=1);

use Core\security\CsrfManager;

$pageTitle = __('updates.title');
$pageDescription = '';
$activeNav = 'core-update';
$breadcrumbs = [
    ['label' => __('common.admin'), 'href' => $adminBase . '/'],
    ['label' => __('nav.system'), 'href' => $adminBase . '/system/monitoring'],
    ['label' => __('updates.title')],
];

$snapshot = is_array($snapshot ?? null) ? $snapshot : [];
$summary = (array) ($snapshot['summary'] ?? []);
$core = (array) ($snapshot['core'] ?? []);
$coreRelease = is_array($core['release'] ?? null) ? $core['release'] : [];
$coreAsset = is_array($core['asset'] ?? null) ? $core['asset'] : null;
$modules = (array) ($snapshot['modules'] ?? []);
$backup = (array) ($snapshot['backup'] ?? []);
$history = (array) ($snapshot['history']['items'] ?? []);

$csrf = htmlspecialchars((new CsrfManager())->token(), ENT_QUOTES, 'UTF-8');
$coreUpdateAvailable = (bool) ($summary['core_update_available'] ?? false);
$coreUpdateRunnable = (bool) ($core['update_runnable'] ?? false);
$modulesWithUpdates = (int) ($summary['modules_with_updates'] ?? 0);
$trustAlerts = (int) ($summary['trust_alerts'] ?? 0);

ob_start();
?>
<link rel="stylesheet" href="/assets/css/catmin-updates.css?v=1">
<section class="card mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-6 col-xl-3"><small class="text-body-secondary d-block"><?= htmlspecialchars(__('updates.summary.core'), ENT_QUOTES, 'UTF-8') ?></small><strong><?= $coreUpdateAvailable ? htmlspecialchars(__('updates.status.core_available'), ENT_QUOTES, 'UTF-8') : htmlspecialchars(__('updates.status.core_uptodate'), ENT_QUOTES, 'UTF-8') ?></strong></div>
            <div class="col-6 col-xl-3"><small class="text-body-secondary d-block"><?= htmlspecialchars(__('updates.summary.modules'), ENT_QUOTES, 'UTF-8') ?></small><strong><?= $modulesWithUpdates ?></strong></div>
            <div class="col-6 col-xl-3"><small class="text-body-secondary d-block"><?= htmlspecialchars(__('updates.summary.alerts'), ENT_QUOTES, 'UTF-8') ?></small><strong class="<?= $trustAlerts > 0 ? 'text-danger' : '' ?>"><?= $trustAlerts ?></strong></div>
            <div class="col-6 col-xl-3"><small class="text-body-secondary d-block"><?= htmlspecialchars(__('updates.summary.last_check'), ENT_QUOTES, 'UTF-8') ?></small><strong><?= htmlspecialchars((string) ($summary['last_check_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></strong></div>
        </div>
    </div>
</section>

<section class="card mb-3">
    <div class="card-header bg-transparent border-0 pt-3 d-flex justify-content-between align-items-center">
        <h3 class="h6 mb-0"><?= htmlspecialchars(__('updates.core.title'), ENT_QUOTES, 'UTF-8') ?></h3>
        <div class="d-flex flex-wrap gap-2">
            <form method="post" action="<?= htmlspecialchars($adminBase . '/system/updates/check', ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                <button class="btn btn-sm btn-outline-secondary" type="submit"><?= htmlspecialchars(__('updates.action.check'), ENT_QUOTES, 'UTF-8') ?></button>
            </form>
            <form method="post" action="<?= htmlspecialchars($adminBase . '/system/updates/dry-run', ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                <button class="btn btn-sm btn-outline-primary" type="submit" <?= $coreAsset ? '' : 'disabled' ?>><?= htmlspecialchars(__('updates.action.dry_run'), ENT_QUOTES, 'UTF-8') ?></button>
            </form>
            <form method="post" action="<?= htmlspecialchars($adminBase . '/system/updates/run', ENT_QUOTES, 'UTF-8') ?>" data-cat-confirm="<?= htmlspecialchars(__('updates.confirm.run'), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                <button class="btn btn-sm btn-primary" type="submit" <?= ($coreUpdateAvailable && $coreUpdateRunnable) ? '' : 'disabled' ?>><?= htmlspecialchars(__('updates.action.run'), ENT_QUOTES, 'UTF-8') ?></button>
            </form>
        </div>
    </div>
    <div class="card-body pt-2">
        <div class="row g-3">
            <div class="col-12 col-md-4"><small class="text-body-secondary d-block"><?= htmlspecialchars(__('updates.core.local'), ENT_QUOTES, 'UTF-8') ?></small><strong><?= htmlspecialchars((string) ($core['local_version'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></strong></div>
            <div class="col-12 col-md-4"><small class="text-body-secondary d-block"><?= htmlspecialchars(__('updates.core.remote'), ENT_QUOTES, 'UTF-8') ?></small><strong><?= htmlspecialchars((string) ($core['remote_version'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></strong></div>
            <div class="col-12 col-md-4"><small class="text-body-secondary d-block"><?= htmlspecialchars(__('common.status'), ENT_QUOTES, 'UTF-8') ?></small>
                <?php if ((bool) ($core['ok'] ?? false)): ?>
                    <span class="badge <?= $coreUpdateAvailable ? 'text-bg-warning' : 'text-bg-success' ?>"><?= htmlspecialchars($coreUpdateAvailable ? __('updates.status.core_available') : __('updates.status.core_uptodate'), ENT_QUOTES, 'UTF-8') ?></span>
                <?php else: ?>
                    <span class="badge text-bg-danger"><?= htmlspecialchars(__('updates.status.error'), ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
            </div>
        </div>
        <div class="mt-3">
            <small class="text-body-secondary d-block mb-1"><?= htmlspecialchars(__('updates.core.changelog'), ENT_QUOTES, 'UTF-8') ?></small>
            <pre class="small border rounded p-3 mb-0 bg-body-tertiary" style="max-height: 220px; overflow:auto;"><?= htmlspecialchars((string) ($coreRelease['body'] ?? __('updates.core.changelog_empty')), ENT_QUOTES, 'UTF-8') ?></pre>
        </div>
    </div>
</section>

<section class="card mb-3">
    <div class="card-header bg-transparent border-0 pt-3 d-flex justify-content-between align-items-center">
        <h3 class="h6 mb-0"><?= htmlspecialchars(__('updates.modules.title'), ENT_QUOTES, 'UTF-8') ?></h3>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
            <tr>
                <th><?= htmlspecialchars(__('common.module'), ENT_QUOTES, 'UTF-8') ?></th>
                <th><?= htmlspecialchars(__('updates.modules.local'), ENT_QUOTES, 'UTF-8') ?></th>
                <th><?= htmlspecialchars(__('updates.modules.remote'), ENT_QUOTES, 'UTF-8') ?></th>
                <th><?= htmlspecialchars(__('market.compatibility'), ENT_QUOTES, 'UTF-8') ?></th>
                <th><?= htmlspecialchars(__('modules.table.integrity'), ENT_QUOTES, 'UTF-8') ?></th>
                <th><?= htmlspecialchars(__('modules.table.signature'), ENT_QUOTES, 'UTF-8') ?></th>
                <th><?= htmlspecialchars(__('modules.table.trust'), ENT_QUOTES, 'UTF-8') ?></th>
                <th>Severity</th>
                <th><?= htmlspecialchars(__('common.status'), ENT_QUOTES, 'UTF-8') ?></th>
            </tr>
            </thead>
            <tbody>
            <?php if ($modules === []): ?>
                <tr><td colspan="9" class="text-center py-4 text-body-secondary"><?= htmlspecialchars(__('updates.modules.empty'), ENT_QUOTES, 'UTF-8') ?></td></tr>
            <?php else: ?>
                <?php foreach ($modules as $row): ?>
                    <?php
                    $integrityStatus = strtolower((string) ($row['integrity_status'] ?? 'unknown'));
                    $signatureStatus = strtolower((string) ($row['signature_status'] ?? 'unknown'));
                    $integrityBadge = match ($integrityStatus) {
                        'valid' => 'text-bg-success',
                        'tampered', 'invalid' => 'text-bg-danger',
                        'missing_checksums', 'unsupported_schema' => 'text-bg-warning',
                        default => 'text-bg-secondary',
                    };
                    $signatureBadge = match ($signatureStatus) {
                        'signed_valid' => 'text-bg-success',
                        'unknown_key' => 'text-bg-warning',
                        'unsigned' => 'text-bg-secondary',
                        default => 'text-bg-danger',
                    };
                    ?>
                    <tr>
                        <td>
                            <p class="mb-0 fw-semibold"><?= htmlspecialchars((string) ($row['name'] ?? $row['slug'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                            <small class="text-body-secondary"><?= htmlspecialchars((string) (($row['type'] ?? '-') . '/' . ($row['slug'] ?? '-')), ENT_QUOTES, 'UTF-8') ?></small>
                        </td>
                        <td><span class="badge text-bg-light border"><?= htmlspecialchars((string) ($row['local_version'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td><span class="badge text-bg-light border"><?= htmlspecialchars((string) ($row['remote_version'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span></td>
                        <?php
                        $compatState = strtolower((string) ($row['compatibility_state'] ?? 'unknown'));
                        $compatClass = match ($compatState) {
                            'compatible' => 'text-bg-success',
                            'compatible_with_warning' => 'text-bg-warning',
                            'incompatible' => 'text-bg-danger',
                            default => 'text-bg-secondary',
                        };
                        ?>
                        <td>
                            <span class="badge <?= $compatClass ?>"><?= htmlspecialchars($compatState, ENT_QUOTES, 'UTF-8') ?></span>
                            <?php foreach ((array) ($row['compat_warnings'] ?? []) as $warning): ?>
                                <small class="d-block text-warning mt-1"><?= htmlspecialchars((string) $warning, ENT_QUOTES, 'UTF-8') ?></small>
                            <?php endforeach; ?>
                        </td>
                        <td><span class="badge <?= $integrityBadge ?>"><?= htmlspecialchars($integrityStatus, ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td><span class="badge <?= $signatureBadge ?>"><?= htmlspecialchars($signatureStatus, ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td><span class="badge <?= (bool) ($row['trusted'] ?? false) ? 'text-bg-success' : 'text-bg-danger' ?>"><?= htmlspecialchars((bool) ($row['trusted'] ?? false) ? __('common.trusted') : __('common.not_trusted'), ENT_QUOTES, 'UTF-8') ?></span></td>
                        <?php
                        $severity = strtolower((string) ($row['update_severity'] ?? 'info'));
                        $severityClass = match ($severity) {
                            'critical' => 'text-bg-danger',
                            'security' => 'text-bg-danger',
                            'important' => 'text-bg-warning',
                            'recommended' => 'text-bg-info',
                            default => 'text-bg-secondary',
                        };
                        ?>
                        <td><span class="badge <?= $severityClass ?>"><?= htmlspecialchars($severity, ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td>
                            <?php if ((bool) ($row['has_update'] ?? false)): ?>
                                <span class="badge text-bg-warning"><?= htmlspecialchars(__('market.status.updates'), ENT_QUOTES, 'UTF-8') ?></span>
                            <?php else: ?>
                                <span class="badge text-bg-success"><?= htmlspecialchars(__('update.status.uptodate'), ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="row g-3 mb-3">
    <div class="col-12 col-xl-6">
        <article class="card h-100">
            <div class="card-header bg-transparent border-0 pt-3"><h3 class="h6 mb-0"><?= htmlspecialchars(__('updates.precheck.title'), ENT_QUOTES, 'UTF-8') ?></h3></div>
            <div class="card-body pt-2">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item px-0 d-flex justify-content-between"><span><?= htmlspecialchars(__('updates.precheck.backup'), ENT_QUOTES, 'UTF-8') ?></span><span class="badge <?= !empty($backup['exists']) ? 'text-bg-success' : 'text-bg-warning' ?>"><?= htmlspecialchars(!empty($backup['exists']) ? __('common.ok') : __('common.warning'), ENT_QUOTES, 'UTF-8') ?></span></li>
                    <li class="list-group-item px-0 d-flex justify-content-between"><span><?= htmlspecialchars(__('updates.precheck.trust'), ENT_QUOTES, 'UTF-8') ?></span><span class="badge <?= $trustAlerts > 0 ? 'text-bg-danger' : 'text-bg-success' ?>"><?= $trustAlerts ?></span></li>
                    <li class="list-group-item px-0 d-flex justify-content-between"><span><?= htmlspecialchars(__('updates.precheck.modules_updates'), ENT_QUOTES, 'UTF-8') ?></span><span class="badge text-bg-warning"><?= $modulesWithUpdates ?></span></li>
                </ul>
            </div>
        </article>
    </div>
    <div class="col-12 col-xl-6">
        <article class="card h-100">
            <div class="card-header bg-transparent border-0 pt-3"><h3 class="h6 mb-0"><?= htmlspecialchars(__('updates.backup.title'), ENT_QUOTES, 'UTF-8') ?></h3></div>
            <div class="card-body pt-2">
                <?php if (!empty($backup['exists'])): ?>
                    <p class="mb-1"><strong><?= htmlspecialchars((string) ($backup['file'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></strong></p>
                    <p class="small text-body-secondary mb-0"><?= htmlspecialchars((string) ($backup['time'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                <?php else: ?>
                    <p class="text-body-secondary mb-0"><?= htmlspecialchars(__('updates.backup.empty'), ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
            </div>
        </article>
    </div>
</section>

<section class="card">
    <div class="card-header bg-transparent border-0 pt-3"><h3 class="h6 mb-0"><?= htmlspecialchars(__('updates.history.title'), ENT_QUOTES, 'UTF-8') ?></h3></div>
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead>
            <tr>
                <th><?= htmlspecialchars(__('updates.history.file'), ENT_QUOTES, 'UTF-8') ?></th>
                <th><?= htmlspecialchars(__('common.status'), ENT_QUOTES, 'UTF-8') ?></th>
                <th><?= htmlspecialchars(__('common.date'), ENT_QUOTES, 'UTF-8') ?></th>
                <th><?= htmlspecialchars(__('updates.history.mode'), ENT_QUOTES, 'UTF-8') ?></th>
                <th><?= htmlspecialchars(__('common.message'), ENT_QUOTES, 'UTF-8') ?></th>
            </tr>
            </thead>
            <tbody>
            <?php if ($history === []): ?>
                <tr><td colspan="5" class="text-center py-4 text-body-secondary"><?= htmlspecialchars(__('updates.history.empty'), ENT_QUOTES, 'UTF-8') ?></td></tr>
            <?php else: ?>
                <?php foreach ($history as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) ($item['file'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><span class="badge <?= (bool) ($item['ok'] ?? false) ? 'text-bg-success' : 'text-bg-danger' ?>"><?= htmlspecialchars((bool) ($item['ok'] ?? false) ? __('common.success') : __('common.error'), ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td><?= htmlspecialchars((string) ($item['started_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((bool) ($item['dry_run'] ?? false) ? 'dry-run' : 'apply', ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="text-body-secondary"><?= htmlspecialchars((string) ($item['error'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<script src="/assets/js/catmin-updates.js?v=1"></script>
<?php
$content = (string) ob_get_clean();
require CATMIN_ADMIN . '/views/layouts/admin.php';
