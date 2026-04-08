<?php

declare(strict_types=1);

use Core\security\CsrfManager;

$tasks = isset($tasks) && is_array($tasks) ? $tasks : [];
$history = isset($history) && is_array($history) ? $history : [];
$message = trim((string) ($message ?? ''));
$messageType = trim((string) ($messageType ?? 'success'));

$pageTitle = 'Cron';
$pageDescription = '';
$activeNav = 'cron';
$breadcrumbs = [
    ['label' => 'Admin', 'href' => $adminBase . '/'],
    ['label' => 'Cron'],
];

$csrf = htmlspecialchars((new CsrfManager())->token(), ENT_QUOTES, 'UTF-8');

ob_start();
?>
<?php if ($message !== ''): ?>
    <div class="alert alert-<?= htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8') ?> py-2 mb-3">
        <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<section class="card mb-3">
    <div class="card-header bg-transparent border-0 pt-3">
        <h3 class="h6 mb-0">Creer une tache cron PHP</h3>
    </div>
    <div class="card-body pt-2">
        <form method="post" action="<?= htmlspecialchars($adminBase . '/cron/create', ENT_QUOTES, 'UTF-8') ?>" class="row g-2">
            <input type="hidden" name="_csrf" value="<?= $csrf ?>">
            <div class="col-12 col-lg-3">
                <label class="form-label">Nom</label>
                <input class="form-control" name="name" placeholder="Ex: Cleanup cache" required>
            </div>
            <div class="col-12 col-lg-4">
                <label class="form-label">Script PHP utilisateur (relatif CATMIN)</label>
                <input class="form-control" name="script_path" placeholder="cron/cleanup.php" required>
            </div>
            <div class="col-8 col-lg-3">
                <label class="form-label">Schedule (crontab)</label>
                <input class="form-control" name="schedule_expr" value="*/5 * * * *" required>
            </div>
            <div class="col-4 col-lg-2">
                <label class="form-label d-block">Actif</label>
                <div class="form-check form-switch mt-2">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
                </div>
            </div>
            <div class="col-12">
                <div class="form-text mb-2">Scripts utilisateur autorises uniquement dans <code>catmin/cron</code>. Les scripts <code>core/cron</code> sont reserves aux taches CATMIN.</div>
                <button class="btn btn-primary" type="submit">Ajouter la tache</button>
            </div>
        </form>
    </div>
</section>

<section class="card mb-3">
    <div class="card-header bg-transparent border-0 pt-3">
        <h3 class="h6 mb-0">Taches cron</h3>
    </div>
    <div class="card-body pt-2">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                <tr>
                    <th>Nom</th>
                    <th>Script</th>
                    <th>Schedule</th>
                    <th>Etat</th>
                    <th>Derniere execution</th>
                    <th class="text-end">Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php if ($tasks === []): ?>
                    <tr><td colspan="6" class="text-body-secondary">Aucune tache cron.</td></tr>
                <?php else: ?>
                    <?php foreach ($tasks as $task): ?>
                        <?php $isActive = ((int) ($task['is_active'] ?? 0)) === 1; ?>
                        <tr>
                            <td><?= htmlspecialchars((string) ($task['name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><code><?= htmlspecialchars((string) ($task['script_path'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></code></td>
                            <td><code><?= htmlspecialchars((string) ($task['schedule_expr'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></code></td>
                            <td>
                                <span class="badge <?= $isActive ? 'text-bg-success' : 'text-bg-secondary' ?>">
                                    <?= $isActive ? 'Actif' : 'Inactif' ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars((string) ($task['last_run_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <form method="post" action="<?= htmlspecialchars($adminBase . '/cron/run', ENT_QUOTES, 'UTF-8') ?>" class="d-inline">
                                        <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                                        <input type="hidden" name="id" value="<?= (int) ($task['id'] ?? 0) ?>">
                                        <button class="btn btn-sm btn-outline-primary" type="submit">Run</button>
                                    </form>
                                    <form method="post" action="<?= htmlspecialchars($adminBase . '/cron/toggle', ENT_QUOTES, 'UTF-8') ?>" class="d-inline">
                                        <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                                        <input type="hidden" name="id" value="<?= (int) ($task['id'] ?? 0) ?>">
                                        <input type="hidden" name="active" value="<?= $isActive ? '0' : '1' ?>">
                                        <button class="btn btn-sm btn-outline-secondary" type="submit"><?= $isActive ? 'Desactiver' : 'Activer' ?></button>
                                    </form>
                                    <form method="post" action="<?= htmlspecialchars($adminBase . '/cron/delete', ENT_QUOTES, 'UTF-8') ?>" class="d-inline">
                                        <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                                        <input type="hidden" name="id" value="<?= (int) ($task['id'] ?? 0) ?>">
                                        <button class="btn btn-sm btn-outline-danger" type="submit" onclick="return confirm('Supprimer cette tache cron ?');">Supprimer</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<section class="card">
    <div class="card-header bg-transparent border-0 pt-3">
        <h3 class="h6 mb-0">Historique cron</h3>
    </div>
    <div class="card-body pt-2">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                <tr><th>Date</th><th>Niveau</th><th>Message</th></tr>
                </thead>
                <tbody>
                <?php if ($history === []): ?>
                    <tr><td colspan="3" class="text-body-secondary">Aucun historique cron.</td></tr>
                <?php else: ?>
                    <?php foreach ($history as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars((string) ($row['created_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><span class="badge text-bg-dark"><?= htmlspecialchars(strtoupper((string) ($row['level'] ?? 'INFO')), ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td><code><?= htmlspecialchars((string) ($row['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?></code></td>
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
