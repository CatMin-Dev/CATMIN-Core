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
$cronHumanize = static function (string $expr): string {
    $expr = trim(preg_replace('/\s+/', ' ', $expr) ?? '');
    if ($expr === '') {
        return 'Expression vide';
    }

    $parts = explode(' ', $expr);
    if (count($parts) !== 5) {
        return 'Format cron invalide (attendu: m h dom mon dow)';
    }

    [$min, $hour, $dom, $mon, $dow] = $parts;

    if (preg_match('/^\*\/([0-9]+)$/', $min, $m) === 1 && $hour === '*' && $dom === '*' && $mon === '*' && $dow === '*') {
        return 'Toutes les ' . (int) $m[1] . ' minute(s)';
    }
    if (ctype_digit($min) && $hour === '*' && $dom === '*' && $mon === '*' && $dow === '*') {
        return 'Chaque heure à ' . str_pad($min, 2, '0', STR_PAD_LEFT) . ' minute(s)';
    }
    if (ctype_digit($min) && ctype_digit($hour) && $dom === '*' && $mon === '*' && $dow === '*') {
        return 'Tous les jours à ' . str_pad($hour, 2, '0', STR_PAD_LEFT) . ':' . str_pad($min, 2, '0', STR_PAD_LEFT);
    }
    if (ctype_digit($min) && ctype_digit($hour) && $dom === '*' && $mon === '*' && ctype_digit($dow)) {
        $days = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];
        $idx = max(0, min(6, (int) $dow));
        return 'Chaque ' . $days[$idx] . ' à ' . str_pad($hour, 2, '0', STR_PAD_LEFT) . ':' . str_pad($min, 2, '0', STR_PAD_LEFT);
    }
    if (ctype_digit($min) && ctype_digit($hour) && ctype_digit($dom) && $mon === '*' && $dow === '*') {
        return 'Le jour ' . (int) $dom . ' de chaque mois à ' . str_pad($hour, 2, '0', STR_PAD_LEFT) . ':' . str_pad($min, 2, '0', STR_PAD_LEFT);
    }

    return 'Expression avancée';
};

ob_start();
?>
<section class="card mb-3">
    <div class="card-header bg-transparent border-0 pt-3">
        <h3 class="h6 mb-0">Créer une tâche cron PHP</h3>
    </div>
    <div class="card-body pt-2">
        <form method="post" action="<?= htmlspecialchars($adminBase . '/cron/create', ENT_QUOTES, 'UTF-8') ?>" class="row g-2" data-cron-builder-form>
            <input type="hidden" name="_csrf" value="<?= $csrf ?>">
            <div class="col-12 col-lg-3">
                <label class="form-label">Nom</label>
                <input class="form-control" name="name" placeholder="Ex: Cleanup cache" required>
            </div>
            <div class="col-12 col-lg-4">
                <label class="form-label">Script PHP utilisateur (relatif CATMIN)</label>
                <input class="form-control" name="script_path" placeholder="cron/cleanup.php" required>
            </div>
            <div class="col-12 col-lg-5">
                <label class="form-label">Planification simplifiée</label>
                <div class="input-group">
                    <span class="input-group-text">Mode</span>
                    <select class="form-select" data-cron-frequency>
                        <option value="interval">Toutes les X minutes</option>
                        <option value="hourly">Horaire</option>
                        <option value="daily" selected>Journalier</option>
                        <option value="weekly">Hebdomadaire</option>
                        <option value="monthly">Mensuel</option>
                        <option value="custom">Expert (manuel)</option>
                    </select>
                </div>
                <div class="row g-2 mt-1" data-cron-simple-controls>
                    <div class="col-6 col-lg-3" data-mode="interval">
                        <select class="form-select" data-cron-interval>
                            <?php foreach ([5, 10, 15, 30] as $ival): ?>
                                <option value="<?= $ival ?>" <?= $ival === 5 ? 'selected' : '' ?>><?= $ival ?> min</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-lg-3 d-none" data-mode="hourly">
                        <input type="number" class="form-control" min="0" max="59" value="0" data-cron-hourly-minute placeholder="Minute">
                    </div>
                    <div class="col-6 col-lg-3" data-mode="daily weekly monthly">
                        <input type="time" class="form-control" value="02:00" data-cron-time>
                    </div>
                    <div class="col-6 col-lg-3 d-none" data-mode="weekly">
                        <select class="form-select" data-cron-weekday>
                            <option value="1">Lundi</option>
                            <option value="2">Mardi</option>
                            <option value="3">Mercredi</option>
                            <option value="4">Jeudi</option>
                            <option value="5">Vendredi</option>
                            <option value="6">Samedi</option>
                            <option value="0">Dimanche</option>
                        </select>
                    </div>
                    <div class="col-6 col-lg-3 d-none" data-mode="monthly">
                        <select class="form-select" data-cron-monthday>
                            <?php for ($d = 1; $d <= 28; $d++): ?>
                                <option value="<?= $d ?>" <?= $d === 1 ? 'selected' : '' ?>>Jour <?= $d ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="col-8 col-lg-2">
                <label class="form-label">Expression cron</label>
                <input class="form-control" name="schedule_expr" value="0 2 * * *" data-cron-expression required>
                <div class="form-text" data-cron-human>Tous les jours à 02:00</div>
            </div>
            <div class="col-4 col-lg-2">
                <label class="form-label d-block">Actif</label>
                <div class="form-check form-switch mt-2">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
                </div>
            </div>
            <div class="col-12">
                <div class="form-text mb-2">Scripts utilisateur autorisés uniquement dans <code>catmin/cron</code>. Les scripts <code>core/cron</code> sont réservés aux tâches CATMIN.</div>
                <button class="btn btn-primary" type="submit">Ajouter la tâche</button>
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
                    <th>Traduction</th>
                    <th>Etat</th>
                    <th>Derniere execution</th>
                    <th class="text-end">Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php if ($tasks === []): ?>
                    <tr><td colspan="7" class="text-body-secondary">Aucune tâche cron.</td></tr>
                <?php else: ?>
                    <?php foreach ($tasks as $task): ?>
                        <?php $isActive = ((int) ($task['is_active'] ?? 0)) === 1; ?>
                        <tr>
                            <td><?= htmlspecialchars((string) ($task['name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><code><?= htmlspecialchars((string) ($task['script_path'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></code></td>
                            <td><code><?= htmlspecialchars((string) ($task['schedule_expr'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></code></td>
                            <td class="small text-body-secondary"><?= htmlspecialchars($cronHumanize((string) ($task['schedule_expr'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
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
<script src="/assets/js/catmin-cron.js?v=1"></script>
<?php
$content = (string) ob_get_clean();
require CATMIN_ADMIN . '/views/layouts/admin.php';
