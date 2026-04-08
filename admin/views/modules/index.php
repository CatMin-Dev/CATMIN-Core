<?php

declare(strict_types=1);

use Core\security\CsrfManager;

$rows = isset($rows) && is_array($rows) ? $rows : [];
$stats = isset($stats) && is_array($stats) ? $stats : [];
$scopes = isset($scopes) && is_array($scopes) ? $scopes : [];
$filters = isset($filters) && is_array($filters) ? $filters : [];
$activeView = (string) ($activeView ?? 'manager');
$message = trim((string) ($message ?? ''));
$messageType = trim((string) ($messageType ?? 'success'));

$isStatusView = $activeView === 'status';
$pageTitle = $isStatusView ? 'Modules / Etat activation' : 'Gestionnaire modules';
$pageDescription = '';
$activeNav = $isStatusView ? 'module-status' : 'module-manager';
$breadcrumbs = [
    ['label' => 'Admin', 'href' => $adminBase . '/'],
    ['label' => 'Modules', 'href' => $adminBase . '/modules'],
];
if ($isStatusView) {
    $breadcrumbs[] = ['label' => 'Etat activation'];
}

$csrf = htmlspecialchars((new CsrfManager())->token(), ENT_QUOTES, 'UTF-8');

ob_start();
?>
<?php if ($isStatusView): ?>
    <div class="alert alert-info py-2 mb-3">
        Vue supervision: suivi des activations, dependances et erreurs modules.
    </div>
<?php else: ?>
    <div class="alert alert-secondary py-2 mb-3">
        Vue gestionnaire: active/desactive les modules et controle leurs contraintes.
    </div>
<?php endif; ?>

<section class="row g-3">
    <div class="col-12 col-md-6 col-xl-3">
        <article class="card cat-module-stat-card h-100">
            <div class="card-body">
                <small class="text-body-secondary">Total modules</small>
                <p class="h4 mb-0"><?= (int) ($stats['total'] ?? 0) ?></p>
            </div>
        </article>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <article class="card cat-module-stat-card h-100">
            <div class="card-body">
                <small class="text-body-secondary">Actifs</small>
                <p class="h4 mb-0 text-success"><?= (int) ($stats['active'] ?? 0) ?></p>
            </div>
        </article>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <article class="card cat-module-stat-card h-100">
            <div class="card-body">
                <small class="text-body-secondary">Inactifs</small>
                <p class="h4 mb-0 text-warning"><?= (int) ($stats['inactive'] ?? 0) ?></p>
            </div>
        </article>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <article class="card cat-module-stat-card h-100">
            <div class="card-body">
                <small class="text-body-secondary">Avec erreurs</small>
                <p class="h4 mb-0 text-danger"><?= (int) ($stats['errors'] ?? 0) ?></p>
            </div>
        </article>
    </div>
</section>

<form method="get" class="card mt-3">
    <div class="card-body py-3">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-lg-5">
                <label class="form-label mb-1">Recherche</label>
                <input class="form-control" type="text" name="q" value="<?= htmlspecialchars((string) ($filters['q'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Nom, slug, version, dependance...">
            </div>
            <div class="col-6 col-lg-3">
                <label class="form-label mb-1">Scope</label>
                <select class="form-select" name="scope">
                    <option value="all">Tous</option>
                    <?php foreach ($scopes as $scope): ?>
                        <option value="<?= htmlspecialchars((string) $scope, ENT_QUOTES, 'UTF-8') ?>" <?= ((string) ($filters['scope'] ?? 'all') === (string) $scope) ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string) $scope, ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label mb-1">Statut</label>
                <select class="form-select" name="status">
                    <?php
                    $statusOptions = [
                        'all' => 'Tous',
                        'active' => 'Actifs',
                        'inactive' => 'Inactifs',
                        'error' => 'Erreurs',
                        'issues' => 'A corriger',
                    ];
                    foreach ($statusOptions as $value => $label):
                        ?>
                        <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>" <?= ((string) ($filters['status'] ?? 'all') === $value) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-lg-2">
                <div class="d-grid gap-2">
                    <button class="btn btn-primary" type="submit">Filtrer</button>
                    <a class="btn btn-outline-secondary" href="<?= htmlspecialchars((string) ($isStatusView ? $adminBase . '/modules/status' : $adminBase . '/modules'), ENT_QUOTES, 'UTF-8') ?>">Reset</a>
                </div>
            </div>
        </div>
    </div>
</form>

<section class="card mt-3">
    <div class="table-responsive cat-modules-table-wrap">
        <table class="table align-middle mb-0 cat-modules-table">
            <thead>
            <tr>
                <th>Module</th>
                <th>Version</th>
                <th>Dependances</th>
                <th>Etat</th>
                <th>Erreurs</th>
                <th class="text-end"><?= $isStatusView ? 'Diagnostic' : 'Activation' ?></th>
            </tr>
            </thead>
            <tbody>
            <?php if ($rows === []): ?>
                <tr>
                    <td colspan="6" class="text-center py-5 text-body-secondary">Aucun module trouve pour ce filtre.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($rows as $row): ?>
                    <?php
                    $scope = (string) ($row['scope'] ?? '-');
                    $slug = (string) ($row['slug'] ?? '-');
                    $name = (string) ($row['name'] ?? $slug);
                    $version = (string) ($row['version'] ?? '-');
                    $enabled = (bool) ($row['enabled'] ?? false);
                    $errors = (array) ($row['errors'] ?? []);
                    $dependencies = (array) ($row['dependencies'] ?? []);
                    ?>
                    <tr>
                        <td>
                            <p class="mb-0 fw-semibold"><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></p>
                            <small class="text-body-secondary"><?= htmlspecialchars($scope . '/' . $slug, ENT_QUOTES, 'UTF-8') ?></small>
                        </td>
                        <td><span class="badge text-bg-light border"><?= htmlspecialchars($version, ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td>
                            <?php if ($dependencies === []): ?>
                                <small class="text-body-secondary">Aucune</small>
                            <?php else: ?>
                                <div class="d-flex flex-wrap gap-1">
                                    <?php foreach ($dependencies as $dep): ?>
                                        <span class="badge text-bg-secondary"><?= htmlspecialchars((string) $dep, ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($enabled): ?>
                                <span class="badge text-bg-success">Actif</span>
                            <?php else: ?>
                                <span class="badge text-bg-warning">Inactif</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($errors === []): ?>
                                <span class="badge text-bg-success">OK</span>
                            <?php else: ?>
                                <ul class="mb-0 ps-3 cat-module-errors">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <?php if ($isStatusView): ?>
                                <?php if ($errors !== []): ?>
                                    <span class="badge text-bg-danger">Action requise</span>
                                <?php elseif ($enabled): ?>
                                    <span class="badge text-bg-success">Sain</span>
                                <?php else: ?>
                                    <span class="badge text-bg-warning">Inactif</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <form method="post" action="<?= htmlspecialchars($adminBase . '/modules/toggle', ENT_QUOTES, 'UTF-8') ?>" class="d-inline">
                                    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                                    <input type="hidden" name="scope" value="<?= htmlspecialchars($scope, ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="slug" value="<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="target" value="<?= $enabled ? '0' : '1' ?>">
                                    <input type="hidden" name="return_to" value="<?= $isStatusView ? 'status' : 'manager' ?>">
                                    <button class="btn btn-sm <?= $enabled ? 'btn-outline-danger' : 'btn-outline-success' ?>" type="submit">
                                        <?= $enabled ? 'Desactiver' : 'Activer' ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<script src="/assets/js/catmin-modules.js?v=1"></script>
<?php
$content = (string) ob_get_clean();
require CATMIN_ADMIN . '/views/layouts/admin.php';
