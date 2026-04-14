<?php

declare(strict_types=1);

use Core\security\CsrfManager;

$preview = is_array($preview ?? null) ? $preview : [];
$impact = is_array($preview['impact'] ?? null) ? $preview['impact'] : [];
$errors = is_array($preview['errors'] ?? null) ? $preview['errors'] : [];
$snapshots = is_array($snapshots ?? null) ? $snapshots : [];

$pageTitle = 'Désinstaller module';
$pageDescription = '';
$activeNav = 'module-manager';
$breadcrumbs = [
    ['label' => __('common.admin'), 'href' => $adminBase . '/'],
    ['label' => __('nav.modules'), 'href' => $adminBase . '/modules'],
    ['label' => 'Désinstaller'],
];
$csrf = htmlspecialchars((new CsrfManager())->token(), ENT_QUOTES, 'UTF-8');

ob_start();
?>
<section class="card mb-3">
    <div class="card-body">
        <?php if (!((bool) ($preview['ok'] ?? false))): ?>
            <div class="alert alert-danger mb-0">
                <?= htmlspecialchars('Analyse impossible: ' . implode(', ', array_map('strval', $errors)), ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php else: ?>
            <h3 class="h5 mb-3"><?= htmlspecialchars((string) ($impact['name'] ?? ($impact['slug'] ?? 'module')), ENT_QUOTES, 'UTF-8') ?></h3>
            <p class="mb-2"><strong>Scope/slug:</strong> <?= htmlspecialchars((string) (($impact['scope'] ?? '-') . '/' . ($impact['slug'] ?? '-')), ENT_QUOTES, 'UTF-8') ?></p>
            <p class="mb-2"><strong>Version:</strong> <?= htmlspecialchars((string) ($impact['version'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
            <p class="mb-2"><strong>Dépendances actives:</strong> <?= (int) count((array) ($impact['active_reverse_dependencies'] ?? [])) ?></p>

            <?php if ((bool) ($impact['non_uninstallable'] ?? false)): ?>
                <div class="alert alert-danger">Module marqué non désinstallable (core/critique).</div>
            <?php endif; ?>

            <?php if ((array) ($impact['active_reverse_dependencies'] ?? []) !== []): ?>
                <div class="alert alert-warning">
                    <p class="mb-2">Désinstallation bloquée: dépendances actives.</p>
                    <ul class="mb-0">
                        <?php foreach ((array) ($impact['active_reverse_dependencies'] ?? []) as $dep): ?>
                            <li><?= htmlspecialchars((string) (($dep['name'] ?? '-') . ' (' . ($dep['slug'] ?? '-') . ')'), ENT_QUOTES, 'UTF-8') ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= htmlspecialchars($adminBase . '/modules/uninstall/run', ENT_QUOTES, 'UTF-8') ?>" class="row g-3">
                <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                <input type="hidden" name="scope" value="<?= htmlspecialchars((string) ($impact['scope'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="slug" value="<?= htmlspecialchars((string) ($impact['slug'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                <div class="col-12 col-lg-4">
                    <label class="form-label">Politique données</label>
                    <select class="form-select" name="data_policy">
                        <option value="keep_data">keep_data (safe)</option>
                        <option value="drop_data">drop_data (destructif)</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-check">
                        <input class="form-check-input" type="checkbox" name="confirm" value="1">
                        <span class="form-check-label">Je confirme la désinstallation.</span>
                    </label>
                    <label class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" name="confirm_destructive" value="1">
                        <span class="form-check-label">Je confirme explicitement l'action destructive si la politique choisie est drop_data.</span>
                    </label>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-danger" <?= (((array) ($impact['active_reverse_dependencies'] ?? [])) !== [] || ((bool) ($impact['non_uninstallable'] ?? false))) ? 'disabled' : '' ?>>Désinstaller</button>
                    <a href="<?= htmlspecialchars($adminBase . '/modules', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">Retour</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</section>

<section class="card">
    <div class="card-header bg-transparent"><h3 class="h6 mb-0">Snapshots disponibles</h3></div>
    <div class="card-body">
        <?php if ($snapshots === []): ?>
            <p class="text-body-secondary mb-0">Aucun snapshot.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>ID</th><th>Type</th><th>Date</th><th class="text-end">Action</th></tr></thead>
                    <tbody>
                    <?php foreach ($snapshots as $snapshot): ?>
                        <tr>
                            <td><code><?= htmlspecialchars((string) ($snapshot['snapshot_id'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></code></td>
                            <td><?= htmlspecialchars((string) ($snapshot['type'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) ($snapshot['created_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="text-end">
                                <form method="post" action="<?= htmlspecialchars($adminBase . '/modules/rollback', ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                                    <input type="hidden" name="slug" value="<?= htmlspecialchars((string) ($snapshot['slug'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="snapshot_id" value="<?= htmlspecialchars((string) ($snapshot['snapshot_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                    <button class="btn btn-sm btn-outline-warning" type="submit">Rollback</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php
$content = (string) ob_get_clean();
require CATMIN_ADMIN . '/views/layouts/admin.php';

