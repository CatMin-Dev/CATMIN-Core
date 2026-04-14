<?php

declare(strict_types=1);

use Core\security\CsrfManager;

$state = isset($state) && is_array($state) ? $state : [];
$backups = isset($backups) && is_array($backups) ? $backups : [];
$diagnostics = isset($diagnostics) && is_array($diagnostics) ? $diagnostics : [];
$maintenanceLevels = isset($maintenanceLevels) && is_array($maintenanceLevels) ? $maintenanceLevels : [];
$activeLevelPolicy = isset($activeLevelPolicy) && is_array($activeLevelPolicy) ? $activeLevelPolicy : [];
$message = trim((string) ($message ?? ''));
$messageType = trim((string) ($messageType ?? 'success'));

$pageTitle = __('maintenance.title');
$pageDescription = '';
$activeNav = 'maintenance';
$breadcrumbs = [
    ['label' => __('common.admin'), 'href' => $adminBase . '/'],
    ['label' => __('maintenance.title')],
];

$csrf = htmlspecialchars((new CsrfManager())->token(), ENT_QUOTES, 'UTF-8');
$maintenance = (bool) ($state['maintenance'] ?? false);
$maintenanceLevel = (int) ($state['maintenance_level'] ?? 0);
$maintenanceReason = (string) ($state['maintenance_reason'] ?? '');
$maintenanceMessage = (string) ($state['maintenance_message'] ?? __('maintenance.placeholder.message'));
$maintenanceAllowAdmin = (bool) ($state['maintenance_allow_admin'] ?? true);
$maintenanceAllowedIps = (string) ($state['maintenance_allowed_ips'] ?? '');
$maintenanceAllowedAdminIds = (string) ($state['maintenance_allowed_admin_ids'] ?? '');

$badgeClass = static function (string $tone): string {
    return match (strtolower($tone)) {
        'success', 'ok' => 'text-bg-success',
        'danger', 'error', 'failed' => 'text-bg-danger',
        'warning' => 'text-bg-warning',
        default => 'text-bg-secondary',
    };
};

ob_start();
?>
<section class="card mb-3">
    <div class="card-header bg-transparent border-0 pt-3 d-flex justify-content-between align-items-center">
        <h3 class="h6 mb-0"><?= htmlspecialchars(__('maintenance.section.mode'), ENT_QUOTES, 'UTF-8') ?></h3>
        <span class="badge <?= $maintenance ? 'text-bg-warning' : 'text-bg-success' ?>"><?= htmlspecialchars($maintenance ? __('maintenance.status.enabled') : __('maintenance.status.disabled'), ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <div class="card-body pt-2">
        <form method="post" action="<?= htmlspecialchars($adminBase . '/maintenance/toggle', ENT_QUOTES, 'UTF-8') ?>" class="d-grid gap-3">
            <input type="hidden" name="_csrf" value="<?= $csrf ?>">
            <div class="d-flex align-items-center gap-3">
                <label class="form-check form-switch m-0">
                    <input class="form-check-input" type="checkbox" name="maintenance" value="1" <?= $maintenance ? 'checked' : '' ?>>
                    <span class="form-check-label"><?= htmlspecialchars($maintenance ? __('maintenance.mode.active') : __('maintenance.mode.inactive'), ENT_QUOTES, 'UTF-8') ?></span>
                </label>
                <label class="form-check m-0">
                    <input class="form-check-input" type="checkbox" name="maintenance_allow_admin" value="1" <?= $maintenanceAllowAdmin ? 'checked' : '' ?>>
                    <span class="form-check-label"><?= htmlspecialchars(__('maintenance.allow_admin_except_superadmin'), ENT_QUOTES, 'UTF-8') ?></span>
                </label>
            </div>
            <div class="row g-3">
                <div class="col-12 col-lg-3">
                    <label class="form-label"><?= htmlspecialchars(__('maintenance.level_config_label'), ENT_QUOTES, 'UTF-8') ?></label>
                    <select class="form-select" name="maintenance_level">
                        <?php foreach ($maintenanceLevels as $level): ?>
                            <?php $lvl = (int) ($level['level'] ?? 0); ?>
                            <option value="<?= $lvl ?>" <?= $maintenanceLevel === $lvl ? 'selected' : '' ?>><?= htmlspecialchars((string) ($level['label'] ?? (__('maintenance.level_option_prefix') . ' ' . $lvl)), ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-lg-9">
                    <label class="form-label"><?= htmlspecialchars(__('maintenance.reason'), ENT_QUOTES, 'UTF-8') ?></label>
                    <input class="form-control" type="text" name="maintenance_reason" value="<?= htmlspecialchars($maintenanceReason, ENT_QUOTES, 'UTF-8') ?>" placeholder="<?= htmlspecialchars(__('maintenance.placeholder.reason_db'), ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label"><?= htmlspecialchars(__('maintenance.public_message'), ENT_QUOTES, 'UTF-8') ?></label>
                    <input class="form-control" type="text" name="maintenance_message" value="<?= htmlspecialchars($maintenanceMessage, ENT_QUOTES, 'UTF-8') ?>" placeholder="<?= htmlspecialchars(__('maintenance.placeholder.message'), ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-12 col-lg-6">
                    <label class="form-label"><?= htmlspecialchars(__('maintenance.whitelist_ips'), ENT_QUOTES, 'UTF-8') ?></label>
                    <textarea class="form-control" name="maintenance_allowed_ips" rows="2" placeholder="127.0.0.1, 192.168.1.10"><?= htmlspecialchars($maintenanceAllowedIps, ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
                <div class="col-12 col-lg-6">
                    <label class="form-label"><?= htmlspecialchars(__('maintenance.whitelist_admin_ids'), ENT_QUOTES, 'UTF-8') ?></label>
                    <input class="form-control" type="text" name="maintenance_allowed_admin_ids" value="<?= htmlspecialchars($maintenanceAllowedAdminIds, ENT_QUOTES, 'UTF-8') ?>" placeholder="1,2,3">
                </div>
            </div>
            <div>
                <button class="btn btn-sm btn-primary" type="submit"><?= htmlspecialchars(__('common.apply'), ENT_QUOTES, 'UTF-8') ?></button>
            </div>
        </form>

        <div class="row g-3 mt-1">
            <div class="col-12 col-lg-6">
                <div class="border rounded p-2 small bg-body-tertiary">
                    <strong><?= htmlspecialchars(__('maintenance.policy.title'), ENT_QUOTES, 'UTF-8') ?></strong><br>
                    <?= htmlspecialchars(__('maintenance.policy.access'), ENT_QUOTES, 'UTF-8') ?>: <?= htmlspecialchars((string) ($activeLevelPolicy['access'] ?? '-'), ENT_QUOTES, 'UTF-8') ?><br>
                    <?= htmlspecialchars(__('maintenance.policy.blocked'), ENT_QUOTES, 'UTF-8') ?>: <?= htmlspecialchars((string) ($activeLevelPolicy['blocked'] ?? '-'), ENT_QUOTES, 'UTF-8') ?><br>
                    <?= htmlspecialchars(__('maintenance.policy.allowed'), ENT_QUOTES, 'UTF-8') ?>: <?= htmlspecialchars((string) ($activeLevelPolicy['allowed'] ?? '-'), ENT_QUOTES, 'UTF-8') ?><br>
                    <?= htmlspecialchars(__('maintenance.policy.usage'), ENT_QUOTES, 'UTF-8') ?>: <?= htmlspecialchars((string) ($activeLevelPolicy['usage'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                </div>
            </div>
            <div class="col-12 col-lg-6">
                <div class="border rounded p-2 small bg-body-tertiary">
                    <strong><?= htmlspecialchars(__('maintenance.meta.title'), ENT_QUOTES, 'UTF-8') ?></strong><br>
                    <?= htmlspecialchars(__('maintenance.enabled_by'), ENT_QUOTES, 'UTF-8') ?>: <?= htmlspecialchars((string) ($state['maintenance_enabled_by'] ?? '-'), ENT_QUOTES, 'UTF-8') ?><br>
                    <?= htmlspecialchars(__('maintenance.started_at'), ENT_QUOTES, 'UTF-8') ?>: <?= htmlspecialchars((string) ($state['maintenance_started_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?><br>
                    <?= htmlspecialchars(__('maintenance.last_backup'), ENT_QUOTES, 'UTF-8') ?>: <?= htmlspecialchars((string) ($state['last_backup'] ?? '-'), ENT_QUOTES, 'UTF-8') ?><br>
                    <?= htmlspecialchars(__('maintenance.last_restore'), ENT_QUOTES, 'UTF-8') ?>: <?= htmlspecialchars((string) ($state['last_restore'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                </div>
            </div>
        </div>

        <div class="table-responsive mt-3">
            <table class="table table-sm align-middle mb-0">
                <thead><tr><th><?= htmlspecialchars(__('maintenance.levels.table.level'), ENT_QUOTES, 'UTF-8') ?></th><th><?= htmlspecialchars(__('maintenance.levels.table.access'), ENT_QUOTES, 'UTF-8') ?></th><th><?= htmlspecialchars(__('maintenance.levels.table.blocked'), ENT_QUOTES, 'UTF-8') ?></th><th><?= htmlspecialchars(__('maintenance.levels.table.allowed'), ENT_QUOTES, 'UTF-8') ?></th><th><?= htmlspecialchars(__('maintenance.levels.table.usage'), ENT_QUOTES, 'UTF-8') ?></th></tr></thead>
                <tbody>
                <?php foreach ($maintenanceLevels as $level): ?>
                    <tr>
                        <td><span class="badge <?= ((int) ($level['level'] ?? 0)) === $maintenanceLevel ? 'text-bg-primary' : 'text-bg-secondary' ?>"><?= htmlspecialchars((string) ($level['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td><?= htmlspecialchars((string) ($level['access'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($level['blocked'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($level['allowed'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($level['usage'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<section class="card mb-3">
    <div class="card-header bg-transparent border-0 pt-3 d-flex justify-content-between align-items-center">
        <h3 class="h6 mb-0"><?= htmlspecialchars(__('maintenance.backups'), ENT_QUOTES, 'UTF-8') ?></h3>
        <form method="post" action="<?= htmlspecialchars($adminBase . '/maintenance/backup/create', ENT_QUOTES, 'UTF-8') ?>" class="d-flex gap-2">
            <input type="hidden" name="_csrf" value="<?= $csrf ?>">
            <select class="form-select form-select-sm" name="backup_type">
                <option value="db_only"><?= htmlspecialchars(__('maintenance.backup.types.db_only'), ENT_QUOTES, 'UTF-8') ?></option>
                <option value="files_only"><?= htmlspecialchars(__('maintenance.backup.types.files_only'), ENT_QUOTES, 'UTF-8') ?></option>
                <option value="db_files"><?= htmlspecialchars(__('maintenance.backup.types.db_files'), ENT_QUOTES, 'UTF-8') ?></option>
                <option value="full_instance"><?= htmlspecialchars(__('maintenance.backup.types.full_instance'), ENT_QUOTES, 'UTF-8') ?></option>
                <option value="pre_update_snapshot"><?= htmlspecialchars(__('maintenance.backup.types.pre_update_snapshot'), ENT_QUOTES, 'UTF-8') ?></option>
                <option value="pre_restore_snapshot"><?= htmlspecialchars(__('maintenance.backup.types.pre_restore_snapshot'), ENT_QUOTES, 'UTF-8') ?></option>
            </select>
            <button class="btn btn-sm btn-primary" type="submit" data-loading-text="<?= htmlspecialchars(__('maintenance.loading.creating'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(__('maintenance.create_backup'), ENT_QUOTES, 'UTF-8') ?></button>
        </form>
    </div>
    <div class="card-body pt-2">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                <tr>
                    <th><?= htmlspecialchars(__('maintenance.table.name'), ENT_QUOTES, 'UTF-8') ?></th>
                    <th><?= htmlspecialchars(__('maintenance.table.type'), ENT_QUOTES, 'UTF-8') ?></th>
                    <th><?= htmlspecialchars(__('maintenance.table.size'), ENT_QUOTES, 'UTF-8') ?></th>
                    <th><?= htmlspecialchars(__('maintenance.table.date'), ENT_QUOTES, 'UTF-8') ?></th>
                    <th><?= htmlspecialchars(__('maintenance.table.version'), ENT_QUOTES, 'UTF-8') ?></th>
                    <th><?= htmlspecialchars(__('maintenance.table.format'), ENT_QUOTES, 'UTF-8') ?></th>
                    <th><?= htmlspecialchars(__('maintenance.table.integrity'), ENT_QUOTES, 'UTF-8') ?></th>
                    <th><?= htmlspecialchars(__('maintenance.table.origin'), ENT_QUOTES, 'UTF-8') ?></th>
                    <th class="text-end"><?= htmlspecialchars(__('maintenance.table.actions'), ENT_QUOTES, 'UTF-8') ?></th>
                </tr>
                </thead>
                <tbody>
                <?php if ($backups === []): ?>
                    <tr><td colspan="9" class="text-body-secondary"><?= htmlspecialchars(__('maintenance.empty_backups'), ENT_QUOTES, 'UTF-8') ?></td></tr>
                <?php else: ?>
                    <?php foreach ($backups as $backup): ?>
                        <?php $backupName = (string) ($backup['name'] ?? ''); ?>
                        <tr>
                            <td><?= htmlspecialchars($backupName !== '' ? $backupName : '-', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><span class="badge text-bg-info"><?= htmlspecialchars((string) ($backup['type'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td><?= number_format(((int) ($backup['size'] ?? 0)) / 1024, 1, '.', ' ') ?> KB</td>
                            <td><?= htmlspecialchars((string) ($backup['date'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) ($backup['core_version'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) ($backup['backup_format_version'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><span class="badge <?= $badgeClass((string) ($backup['integrity'] ?? 'secondary')) ?>"><?= htmlspecialchars((string) ($backup['integrity'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td><?= htmlspecialchars((string) ($backup['origin'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars($adminBase . '/maintenance/backup/read?backup=' . rawurlencode($backupName), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(__('maintenance.action.view_details'), ENT_QUOTES, 'UTF-8') ?></a>
                                <a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars($adminBase . '/maintenance/backup/download?backup=' . rawurlencode($backupName), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(__('maintenance.action.download'), ENT_QUOTES, 'UTF-8') ?></a>
                                <form method="post" action="<?= htmlspecialchars($adminBase . '/maintenance/restore', ENT_QUOTES, 'UTF-8') ?>" class="d-inline" data-cat-confirm="<?= htmlspecialchars(__('maintenance.confirm.restore_overwrite'), ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                                    <input type="hidden" name="backup" value="<?= htmlspecialchars($backupName, ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="auto_snapshot" value="1">
                                    <select class="form-select form-select-sm d-inline-block" style="width:auto" name="restore_mode">
                                        <option value="db_only"><?= htmlspecialchars(__('maintenance.action.restore_db'), ENT_QUOTES, 'UTF-8') ?></option>
                                        <option value="files_only"><?= htmlspecialchars(__('maintenance.action.restore_files'), ENT_QUOTES, 'UTF-8') ?></option>
                                        <option value="full"><?= htmlspecialchars(__('maintenance.action.restore_full'), ENT_QUOTES, 'UTF-8') ?></option>
                                    </select>
                                    <label class="form-check-label small ms-1"><input class="form-check-input" type="checkbox" name="dry_run" value="1"> <?= htmlspecialchars(__('maintenance.action.simulate'), ENT_QUOTES, 'UTF-8') ?></label>
                                    <button class="btn btn-sm btn-outline-warning" type="submit"><?= htmlspecialchars(__('maintenance.restore'), ENT_QUOTES, 'UTF-8') ?></button>
                                </form>
                                <form method="post" action="<?= htmlspecialchars($adminBase . '/maintenance/backup/delete', ENT_QUOTES, 'UTF-8') ?>" class="d-inline" data-cat-confirm="<?= htmlspecialchars(__('maintenance.confirm.delete_irreversible'), ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                                    <input type="hidden" name="backup" value="<?= htmlspecialchars($backupName, ENT_QUOTES, 'UTF-8') ?>">
                                    <button class="btn btn-sm btn-outline-danger" type="submit"><?= htmlspecialchars(__('common.delete'), ENT_QUOTES, 'UTF-8') ?></button>
                                </form>
                                <?php if (!empty($backup['is_orphan'])): ?>
                                    <form method="post" action="<?= htmlspecialchars($adminBase . '/maintenance/backup/repair', ENT_QUOTES, 'UTF-8') ?>" class="d-inline" data-cat-confirm="<?= htmlspecialchars(__('maintenance.confirm.repair_orphan'), ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                                        <input type="hidden" name="backup" value="<?= htmlspecialchars($backupName, ENT_QUOTES, 'UTF-8') ?>">
                                        <button class="btn btn-sm btn-outline-secondary" type="submit"><?= htmlspecialchars(__('maintenance.action.repair_index'), ENT_QUOTES, 'UTF-8') ?></button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="alert alert-warning mt-3 mb-0">
            <strong><?= htmlspecialchars(__('maintenance.warning.restore_delete_title'), ENT_QUOTES, 'UTF-8') ?>:</strong> <?= htmlspecialchars(__('maintenance.warning.restore_delete_body'), ENT_QUOTES, 'UTF-8') ?>
        </div>
    </div>
</section>

<section class="card mb-3">
    <div class="card-header bg-transparent border-0 pt-3">
        <h3 class="h6 mb-0"><?= htmlspecialchars(__('maintenance.diagnostics.title'), ENT_QUOTES, 'UTF-8') ?></h3>
    </div>
    <div class="card-body pt-2">
        <div class="row g-3">
            <div class="col-12 col-md-6 col-xl-3"><div class="border rounded p-2"><small class="text-body-secondary d-block"><?= htmlspecialchars(__('maintenance.diagnostics.last_backup'), ENT_QUOTES, 'UTF-8') ?></small><strong><?= htmlspecialchars((string) ($diagnostics['last_backup'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></strong></div></div>
            <div class="col-12 col-md-6 col-xl-3"><div class="border rounded p-2"><small class="text-body-secondary d-block"><?= htmlspecialchars(__('maintenance.diagnostics.last_restore'), ENT_QUOTES, 'UTF-8') ?></small><strong><?= htmlspecialchars((string) ($diagnostics['last_restore'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></strong></div></div>
            <div class="col-12 col-md-6 col-xl-3"><div class="border rounded p-2"><small class="text-body-secondary d-block"><?= htmlspecialchars(__('maintenance.diagnostics.last_failure'), ENT_QUOTES, 'UTF-8') ?></small><strong><?= htmlspecialchars((string) ($diagnostics['last_failure'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></strong></div></div>
            <div class="col-12 col-md-6 col-xl-3"><div class="border rounded p-2"><small class="text-body-secondary d-block"><?= htmlspecialchars(__('maintenance.diagnostics.total_size'), ENT_QUOTES, 'UTF-8') ?></small><strong><?= number_format(((int) ($diagnostics['total_size_bytes'] ?? 0)) / (1024 * 1024), 2, '.', ' ') ?> MB</strong></div></div>
            <div class="col-12 col-md-6 col-xl-3"><div class="border rounded p-2"><small class="text-body-secondary d-block"><?= htmlspecialchars(__('maintenance.diagnostics.backup_format'), ENT_QUOTES, 'UTF-8') ?></small><strong><?= htmlspecialchars((string) ($diagnostics['backup_format_version'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></strong></div></div>
            <div class="col-12 col-md-6 col-xl-3"><div class="border rounded p-2"><small class="text-body-secondary d-block"><?= htmlspecialchars(__('maintenance.diagnostics.core_version'), ENT_QUOTES, 'UTF-8') ?></small><strong><?= htmlspecialchars((string) ($diagnostics['core_version'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></strong></div></div>
            <div class="col-12 col-md-6 col-xl-3"><div class="border rounded p-2"><small class="text-body-secondary d-block"><?= htmlspecialchars(__('maintenance.diagnostics.storage'), ENT_QUOTES, 'UTF-8') ?></small><strong><?= !empty($diagnostics['storage_ok']) ? htmlspecialchars(__('maintenance.diagnostics.storage_ok'), ENT_QUOTES, 'UTF-8') : htmlspecialchars(__('maintenance.diagnostics.storage_error'), ENT_QUOTES, 'UTF-8') ?></strong></div></div>
            <div class="col-12 col-md-6 col-xl-3"><div class="border rounded p-2"><small class="text-body-secondary d-block"><?= htmlspecialchars(__('maintenance.diagnostics.orphans'), ENT_QUOTES, 'UTF-8') ?></small><strong><?= (int) ($diagnostics['orphans'] ?? 0) ?></strong></div></div>
        </div>
        <div class="mt-2 small">
            <a href="<?= htmlspecialchars($adminBase . '/logs', ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(__('maintenance.diagnostics.open_logs'), ENT_QUOTES, 'UTF-8') ?></a>
        </div>
    </div>
</section>

<script>
document.addEventListener('submit', function (event) {
    var form = event.target;
    if (!(form instanceof HTMLFormElement)) {
        return;
    }
    var buttons = form.querySelectorAll('button[type="submit"]');
    buttons.forEach(function (button) {
        button.disabled = true;
        var loadingText = button.getAttribute('data-loading-text');
        if (loadingText) {
            button.dataset.originalText = button.textContent || '';
            button.textContent = loadingText;
        }
    });
});
</script>

<?php
$content = (string) ob_get_clean();
require CATMIN_ADMIN . '/views/layouts/admin.php';
