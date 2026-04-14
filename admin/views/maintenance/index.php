<?php

declare(strict_types=1);

use Core\security\CsrfManager;

$state = isset($state) && is_array($state) ? $state : [];
$backups = isset($backups) && is_array($backups) ? $backups : [];
$diagnostics = isset($diagnostics) && is_array($diagnostics) ? $diagnostics : [];
$audit = isset($audit) && is_array($audit) ? $audit : [];
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
<?php if ($message !== ''): ?>
    <div class="alert alert-<?= htmlspecialchars($messageType === 'danger' ? 'danger' : ($messageType === 'warning' ? 'warning' : 'success'), ENT_QUOTES, 'UTF-8') ?> mb-3">
        <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<section class="card mb-3">
    <div class="card-header bg-transparent border-0 pt-3 d-flex justify-content-between align-items-center">
        <h3 class="h6 mb-0">Bloc A - Maintenance</h3>
        <span class="badge <?= $maintenance ? 'text-bg-warning' : 'text-bg-success' ?>"><?= $maintenance ? 'Maintenance active' : 'Maintenance desactivee' ?></span>
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
                    <label class="form-label">Niveau de maintenance</label>
                    <select class="form-select" name="maintenance_level">
                        <?php foreach ($maintenanceLevels as $level): ?>
                            <?php $lvl = (int) ($level['level'] ?? 0); ?>
                            <option value="<?= $lvl ?>" <?= $maintenanceLevel === $lvl ? 'selected' : '' ?>><?= htmlspecialchars((string) ($level['label'] ?? ('Niveau ' . $lvl)), ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
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

        <div class="row g-3 mt-1">
            <div class="col-12 col-lg-6">
                <div class="border rounded p-2 small bg-body-tertiary">
                    <strong>Politique active</strong><br>
                    Acces: <?= htmlspecialchars((string) ($activeLevelPolicy['access'] ?? '-'), ENT_QUOTES, 'UTF-8') ?><br>
                    Bloque: <?= htmlspecialchars((string) ($activeLevelPolicy['blocked'] ?? '-'), ENT_QUOTES, 'UTF-8') ?><br>
                    Autorise: <?= htmlspecialchars((string) ($activeLevelPolicy['allowed'] ?? '-'), ENT_QUOTES, 'UTF-8') ?><br>
                    Usage: <?= htmlspecialchars((string) ($activeLevelPolicy['usage'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                </div>
            </div>
            <div class="col-12 col-lg-6">
                <div class="border rounded p-2 small bg-body-tertiary">
                    <strong>Meta</strong><br>
                    Active par: <?= htmlspecialchars((string) ($state['maintenance_enabled_by'] ?? '-'), ENT_QUOTES, 'UTF-8') ?><br>
                    Debut: <?= htmlspecialchars((string) ($state['maintenance_started_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?><br>
                    Dernier backup: <?= htmlspecialchars((string) ($state['last_backup'] ?? '-'), ENT_QUOTES, 'UTF-8') ?><br>
                    Dernier restore: <?= htmlspecialchars((string) ($state['last_restore'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                </div>
            </div>
        </div>

        <div class="table-responsive mt-3">
            <table class="table table-sm align-middle mb-0">
                <thead><tr><th>Niveau</th><th>Acces</th><th>Bloque</th><th>Autorise</th><th>Usage</th></tr></thead>
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
        <h3 class="h6 mb-0">Bloc B - Sauvegardes</h3>
        <form method="post" action="<?= htmlspecialchars($adminBase . '/maintenance/backup/create', ENT_QUOTES, 'UTF-8') ?>" class="d-flex gap-2">
            <input type="hidden" name="_csrf" value="<?= $csrf ?>">
            <select class="form-select form-select-sm" name="backup_type">
                <option value="db_only">DB only</option>
                <option value="files_only">Files only</option>
                <option value="db_files">DB + files</option>
                <option value="full_instance">Full instance</option>
                <option value="pre_update_snapshot">Pre-update snapshot</option>
                <option value="pre_restore_snapshot">Pre-restore snapshot</option>
            </select>
            <button class="btn btn-sm btn-primary" type="submit" data-loading-text="Creation...">Creer sauvegarde</button>
        </form>
    </div>
    <div class="card-body pt-2">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                <tr>
                    <th>Nom</th>
                    <th>Type</th>
                    <th>Taille</th>
                    <th>Date</th>
                    <th>Version</th>
                    <th>Format</th>
                    <th>Integrite</th>
                    <th>Origine</th>
                    <th class="text-end">Actions</th>
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
                                <a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars($adminBase . '/maintenance/backup/read?backup=' . rawurlencode($backupName), ENT_QUOTES, 'UTF-8') ?>">Voir detail</a>
                                <a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars($adminBase . '/maintenance/backup/download?backup=' . rawurlencode($backupName), ENT_QUOTES, 'UTF-8') ?>">Telecharger</a>
                                <form method="post" action="<?= htmlspecialchars($adminBase . '/maintenance/restore', ENT_QUOTES, 'UTF-8') ?>" class="d-inline" data-cat-confirm="Cette restauration peut ecraser des donnees. Continuer ?">
                                    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                                    <input type="hidden" name="backup" value="<?= htmlspecialchars($backupName, ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="auto_snapshot" value="1">
                                    <select class="form-select form-select-sm d-inline-block" style="width:auto" name="restore_mode">
                                        <option value="db_only">restore DB only</option>
                                        <option value="files_only">restore files only</option>
                                        <option value="full">restore full</option>
                                    </select>
                                    <label class="form-check-label small ms-1"><input class="form-check-input" type="checkbox" name="dry_run" value="1"> dry-run</label>
                                    <button class="btn btn-sm btn-outline-warning" type="submit"><?= htmlspecialchars(__('maintenance.restore'), ENT_QUOTES, 'UTF-8') ?></button>
                                </form>
                                <form method="post" action="<?= htmlspecialchars($adminBase . '/maintenance/backup/delete', ENT_QUOTES, 'UTF-8') ?>" class="d-inline" data-cat-confirm="Suppression irreversible. Continuer ?">
                                    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                                    <input type="hidden" name="backup" value="<?= htmlspecialchars($backupName, ENT_QUOTES, 'UTF-8') ?>">
                                    <button class="btn btn-sm btn-outline-danger" type="submit"><?= htmlspecialchars(__('common.delete'), ENT_QUOTES, 'UTF-8') ?></button>
                                </form>
                                <?php if (!empty($backup['is_orphan'])): ?>
                                    <form method="post" action="<?= htmlspecialchars($adminBase . '/maintenance/backup/repair', ENT_QUOTES, 'UTF-8') ?>" class="d-inline" data-cat-confirm="Reparer l'index pour cette entree orpheline ?">
                                        <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                                        <input type="hidden" name="backup" value="<?= htmlspecialchars($backupName, ENT_QUOTES, 'UTF-8') ?>">
                                        <button class="btn btn-sm btn-outline-secondary" type="submit">Reparer index</button>
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
            <strong>Danger restauration/suppression:</strong> les actions critiques sont journalisees, protegees CSRF, et bloquees en concurrence.
        </div>
    </div>
</section>

<section class="card mb-3">
    <div class="card-header bg-transparent border-0 pt-3">
        <h3 class="h6 mb-0">Bloc C - Diagnostics</h3>
    </div>
    <div class="card-body pt-2">
        <div class="row g-3">
            <div class="col-12 col-md-6 col-xl-3"><div class="border rounded p-2"><small class="text-body-secondary d-block">Dernier backup</small><strong><?= htmlspecialchars((string) ($diagnostics['last_backup'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></strong></div></div>
            <div class="col-12 col-md-6 col-xl-3"><div class="border rounded p-2"><small class="text-body-secondary d-block">Dernier restore</small><strong><?= htmlspecialchars((string) ($diagnostics['last_restore'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></strong></div></div>
            <div class="col-12 col-md-6 col-xl-3"><div class="border rounded p-2"><small class="text-body-secondary d-block">Dernier echec</small><strong><?= htmlspecialchars((string) ($diagnostics['last_failure'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></strong></div></div>
            <div class="col-12 col-md-6 col-xl-3"><div class="border rounded p-2"><small class="text-body-secondary d-block">Taille totale backups</small><strong><?= number_format(((int) ($diagnostics['total_size_bytes'] ?? 0)) / (1024 * 1024), 2, '.', ' ') ?> MB</strong></div></div>
            <div class="col-12 col-md-6 col-xl-3"><div class="border rounded p-2"><small class="text-body-secondary d-block">backup_format</small><strong><?= htmlspecialchars((string) ($diagnostics['backup_format_version'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></strong></div></div>
            <div class="col-12 col-md-6 col-xl-3"><div class="border rounded p-2"><small class="text-body-secondary d-block">Version CATMIN</small><strong><?= htmlspecialchars((string) ($diagnostics['core_version'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></strong></div></div>
            <div class="col-12 col-md-6 col-xl-3"><div class="border rounded p-2"><small class="text-body-secondary d-block">Stockage backup</small><strong><?= !empty($diagnostics['storage_ok']) ? 'OK' : 'ERREUR' ?></strong></div></div>
            <div class="col-12 col-md-6 col-xl-3"><div class="border rounded p-2"><small class="text-body-secondary d-block">Orphelins</small><strong><?= (int) ($diagnostics['orphans'] ?? 0) ?></strong></div></div>
        </div>
    </div>
</section>

<section class="card mb-3">
    <div class="card-header bg-transparent border-0 pt-3">
        <h3 class="h6 mb-0">Bloc D - Journal d'audit</h3>
    </div>
    <div class="card-body pt-2">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead><tr><th>Date</th><th>Action</th><th>Resultat</th><th>Auteur</th><th>IP</th><th>Message</th></tr></thead>
                <tbody>
                <?php if ($audit === []): ?>
                    <tr><td colspan="6" class="text-body-secondary">Aucun evenement audite pour le moment.</td></tr>
                <?php else: ?>
                    <?php foreach ($audit as $event): ?>
                        <tr>
                            <td><?= htmlspecialchars((string) ($event['created_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><code><?= htmlspecialchars((string) ($event['action'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></code></td>
                            <td><span class="badge <?= $badgeClass((string) ($event['result'] ?? 'secondary')) ?>"><?= htmlspecialchars((string) ($event['result'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td><?= htmlspecialchars((string) ($event['actor_username'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) ($event['ip_address'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) ($event['message'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
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
