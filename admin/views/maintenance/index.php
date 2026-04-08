<?php

declare(strict_types=1);

use Core\security\CsrfManager;

$state = isset($state) && is_array($state) ? $state : [];
$backups = isset($backups) && is_array($backups) ? $backups : [];
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
$maintenanceLevel = (int) ($state['maintenance_level'] ?? 1);
$maintenanceReason = (string) ($state['maintenance_reason'] ?? '');
$maintenanceMessage = (string) ($state['maintenance_message'] ?? __('maintenance.placeholder.message'));
$maintenanceAllowAdmin = (bool) ($state['maintenance_allow_admin'] ?? true);
$maintenanceAllowedIps = (string) ($state['maintenance_allowed_ips'] ?? '');
$maintenanceAllowedAdminIds = (string) ($state['maintenance_allowed_admin_ids'] ?? '');

ob_start();
?>
<section class="card mb-3">
    <div class="card-header bg-transparent border-0 pt-3">
        <h3 class="h6 mb-0"><?= htmlspecialchars(__('maintenance.section.mode'), ENT_QUOTES, 'UTF-8') ?></h3>
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
                    <label class="form-label"><?= htmlspecialchars(__('maintenance.level'), ENT_QUOTES, 'UTF-8') ?></label>
                    <select class="form-select" name="maintenance_level">
                        <?php for ($i = 1; $i <= 3; $i++): ?>
                            <option value="<?= $i ?>" <?= $maintenanceLevel === $i ? 'selected' : '' ?>><?= htmlspecialchars(__('maintenance.level_label'), ENT_QUOTES, 'UTF-8') ?> <?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-12 col-lg-9">
                    <label class="form-label"><?= htmlspecialchars(__('maintenance.reason'), ENT_QUOTES, 'UTF-8') ?></label>
                    <input class="form-control" type="text" name="maintenance_reason" value="<?= htmlspecialchars($maintenanceReason, ENT_QUOTES, 'UTF-8') ?>" placeholder="<?= htmlspecialchars(__('maintenance.placeholder.reason'), ENT_QUOTES, 'UTF-8') ?>">
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
        <p class="small text-body-secondary mt-3 mb-1"><?= htmlspecialchars(__('maintenance.enabled_by'), ENT_QUOTES, 'UTF-8') ?>: <?= htmlspecialchars((string) ($state['maintenance_enabled_by'] ?? '-'), ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars(__('maintenance.started_at'), ENT_QUOTES, 'UTF-8') ?>: <?= htmlspecialchars((string) ($state['maintenance_started_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
        <p class="small text-body-secondary mb-1"><?= htmlspecialchars(__('maintenance.last_backup'), ENT_QUOTES, 'UTF-8') ?>: <?= htmlspecialchars((string) ($state['last_backup'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
        <p class="small text-body-secondary mb-0"><?= htmlspecialchars(__('maintenance.last_restore'), ENT_QUOTES, 'UTF-8') ?>: <?= htmlspecialchars((string) ($state['last_restore'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
    </div>
</section>

<section class="card mb-3">
    <div class="card-header bg-transparent border-0 pt-3 d-flex justify-content-between align-items-center">
        <h3 class="h6 mb-0"><?= htmlspecialchars(__('maintenance.backups'), ENT_QUOTES, 'UTF-8') ?></h3>
        <form method="post" action="<?= htmlspecialchars($adminBase . '/maintenance/backup/create', ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="_csrf" value="<?= $csrf ?>">
            <button class="btn btn-sm btn-primary" type="submit"><?= htmlspecialchars(__('maintenance.create_backup'), ENT_QUOTES, 'UTF-8') ?></button>
        </form>
    </div>
    <div class="card-body pt-2">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                <tr><th><?= htmlspecialchars(__('maintenance.table.file'), ENT_QUOTES, 'UTF-8') ?></th><th><?= htmlspecialchars(__('maintenance.table.size'), ENT_QUOTES, 'UTF-8') ?></th><th><?= htmlspecialchars(__('logs.table.date'), ENT_QUOTES, 'UTF-8') ?></th><th class="text-end"><?= htmlspecialchars(__('common.actions'), ENT_QUOTES, 'UTF-8') ?></th></tr>
                </thead>
                <tbody>
                <?php if ($backups === []): ?>
                    <tr><td colspan="4" class="text-body-secondary"><?= htmlspecialchars(__('maintenance.empty_backups'), ENT_QUOTES, 'UTF-8') ?></td></tr>
                <?php else: ?>
                    <?php foreach ($backups as $backup): ?>
                        <tr>
                            <td><?= htmlspecialchars((string) ($backup['name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= number_format(((int) ($backup['size'] ?? 0)) / 1024, 1, '.', ' ') ?> KB</td>
                            <td><?= htmlspecialchars((string) ($backup['date'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars($adminBase . '/maintenance/backup/download?backup=' . rawurlencode((string) ($backup['name'] ?? '')), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(__('common.download'), ENT_QUOTES, 'UTF-8') ?></a>
                                <a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars($adminBase . '/maintenance/backup/read?backup=' . rawurlencode((string) ($backup['name'] ?? '')), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(__('common.read'), ENT_QUOTES, 'UTF-8') ?></a>
                                <form method="post" action="<?= htmlspecialchars($adminBase . '/maintenance/restore', ENT_QUOTES, 'UTF-8') ?>" class="d-inline">
                                    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                                    <input type="hidden" name="backup" value="<?= htmlspecialchars((string) ($backup['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                    <button class="btn btn-sm btn-outline-warning" type="submit" onclick="return confirm('<?= htmlspecialchars(__('maintenance.confirm_restore'), ENT_QUOTES, 'UTF-8') ?>');"><?= htmlspecialchars(__('maintenance.restore'), ENT_QUOTES, 'UTF-8') ?></button>
                                </form>
                                <form method="post" action="<?= htmlspecialchars($adminBase . '/maintenance/backup/delete', ENT_QUOTES, 'UTF-8') ?>" class="d-inline">
                                    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                                    <input type="hidden" name="backup" value="<?= htmlspecialchars((string) ($backup['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                    <button class="btn btn-sm btn-outline-danger" type="submit" onclick="return confirm('<?= htmlspecialchars(__('maintenance.confirm_delete_backup'), ENT_QUOTES, 'UTF-8') ?>');"><?= htmlspecialchars(__('common.delete'), ENT_QUOTES, 'UTF-8') ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php
$content = (string) ob_get_clean();
require CATMIN_ADMIN . '/views/layouts/admin.php';
