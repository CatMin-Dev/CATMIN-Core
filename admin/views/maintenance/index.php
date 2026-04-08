<?php

declare(strict_types=1);

use Core\security\CsrfManager;

$state = isset($state) && is_array($state) ? $state : [];
$backups = isset($backups) && is_array($backups) ? $backups : [];
$message = trim((string) ($message ?? ''));
$messageType = trim((string) ($messageType ?? 'success'));

$pageTitle = 'Backup / Maintenance';
$pageDescription = '';
$activeNav = 'maintenance';
$breadcrumbs = [
    ['label' => 'Admin', 'href' => $adminBase . '/'],
    ['label' => 'Backup / Maintenance'],
];

$csrf = htmlspecialchars((new CsrfManager())->token(), ENT_QUOTES, 'UTF-8');
$maintenance = (bool) ($state['maintenance'] ?? false);

ob_start();
?>
<?php if ($message !== ''): ?>
    <div class="alert alert-<?= htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8') ?> py-2 mb-3">
        <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<section class="card mb-3">
    <div class="card-header bg-transparent border-0 pt-3">
        <h3 class="h6 mb-0">Maintenance</h3>
    </div>
    <div class="card-body pt-2">
        <form method="post" action="<?= htmlspecialchars($adminBase . '/maintenance/toggle', ENT_QUOTES, 'UTF-8') ?>" class="d-flex align-items-center gap-3">
            <input type="hidden" name="_csrf" value="<?= $csrf ?>">
            <label class="form-check form-switch m-0">
                <input class="form-check-input" type="checkbox" name="maintenance" value="1" <?= $maintenance ? 'checked' : '' ?>>
                <span class="form-check-label"><?= $maintenance ? 'Mode active' : 'Mode inactif' ?></span>
            </label>
            <button class="btn btn-sm btn-primary" type="submit">Appliquer</button>
        </form>
        <p class="small text-body-secondary mt-3 mb-1">Dernier backup: <?= htmlspecialchars((string) ($state['last_backup'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
        <p class="small text-body-secondary mb-0">Dernier restore: <?= htmlspecialchars((string) ($state['last_restore'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
    </div>
</section>

<section class="card mb-3">
    <div class="card-header bg-transparent border-0 pt-3 d-flex justify-content-between align-items-center">
        <h3 class="h6 mb-0">Sauvegardes</h3>
        <form method="post" action="<?= htmlspecialchars($adminBase . '/maintenance/backup/create', ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="_csrf" value="<?= $csrf ?>">
            <button class="btn btn-sm btn-primary" type="submit">Creer backup</button>
        </form>
    </div>
    <div class="card-body pt-2">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                <tr><th>Fichier</th><th>Taille</th><th>Date</th><th class="text-end">Restore</th></tr>
                </thead>
                <tbody>
                <?php if ($backups === []): ?>
                    <tr><td colspan="4" class="text-body-secondary">Aucun backup disponible.</td></tr>
                <?php else: ?>
                    <?php foreach ($backups as $backup): ?>
                        <tr>
                            <td><?= htmlspecialchars((string) ($backup['name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= number_format(((int) ($backup['size'] ?? 0)) / 1024, 1, '.', ' ') ?> KB</td>
                            <td><?= htmlspecialchars((string) ($backup['date'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="text-end">
                                <form method="post" action="<?= htmlspecialchars($adminBase . '/maintenance/restore', ENT_QUOTES, 'UTF-8') ?>" class="d-inline">
                                    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                                    <input type="hidden" name="backup" value="<?= htmlspecialchars((string) ($backup['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                    <button class="btn btn-sm btn-outline-warning" type="submit" onclick="return confirm('Confirmer la restauration simulee de cette sauvegarde ?');">Restore</button>
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
