<?php

declare(strict_types=1);

$apps = isset($apps) && is_array($apps) ? $apps : [];
$activeSettingsNav = (string) ($activeSettingsNav ?? 'apps');

$pageTitle = 'Paramètres · Apps';
$pageDescription = '';
$activeNav = $activeSettingsNav;
$breadcrumbs = [
    ['label' => 'Admin', 'href' => $adminBase . '/'],
    ['label' => 'Paramètres', 'href' => $adminBase . '/settings/general'],
    ['label' => 'Apps'],
];

$csrf = htmlspecialchars((new \Core\security\CsrfManager())->token(), ENT_QUOTES, 'UTF-8');

ob_start();
?>
<section class="card">
    <div class="card-header bg-transparent border-0 pt-3">
        <h3 class="h6 mb-0">Launcher Apps (Topbar)</h3>
    </div>
    <div class="card-body pt-2">
        <form method="post" action="<?= htmlspecialchars($adminBase . '/settings/apps', ENT_QUOTES, 'UTF-8') ?>" class="row g-3 mb-4">
            <input type="hidden" name="_csrf" value="<?= $csrf ?>">
            <input type="hidden" name="action" value="create">
            <div class="col-12 col-lg-3">
                <label class="form-label">Label</label>
                <input class="form-control" type="text" name="label" placeholder="GPT">
            </div>
            <div class="col-12 col-lg-3">
                <label class="form-label">URL</label>
                <input class="form-control" type="text" name="url" placeholder="https://chat.openai.com">
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label">Type</label>
                <select class="form-select" name="type">
                    <option value="external">External</option>
                    <option value="internal">Internal</option>
                </select>
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label">Target</label>
                <select class="form-select" name="target">
                    <option value="_blank">New tab</option>
                    <option value="_self">Same tab</option>
                </select>
            </div>
            <div class="col-6 col-lg-1">
                <label class="form-label">Ordre</label>
                <input class="form-control" type="number" min="1" name="sort_order" value="100">
            </div>
            <div class="col-6 col-lg-1">
                <label class="form-label">On</label>
                <div class="form-check form-switch mt-2">
                    <input class="form-check-input" type="checkbox" name="is_enabled" value="1" checked>
                </div>
            </div>
            <div class="col-12 col-lg-4">
                <label class="form-label">Icône CSS / image URL</label>
                <input class="form-control" type="text" name="icon" placeholder="bi bi-robot ou /assets/icons/app.svg">
            </div>
            <div class="col-12 col-lg-2 align-self-end">
                <button class="btn btn-primary w-100" type="submit">Ajouter</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Label</th>
                        <th>URL</th>
                        <th>Type</th>
                        <th>Target</th>
                        <th>Ordre</th>
                        <th>Etat</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($apps === []): ?>
                        <tr><td colspan="8" class="text-body-secondary">Aucune app configurée.</td></tr>
                    <?php else: ?>
                        <?php foreach ($apps as $app): ?>
                            <?php $appId = (int) ($app['id'] ?? 0); ?>
                            <tr>
                                <td><?= $appId ?></td>
                                <td><?= htmlspecialchars((string) ($app['label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><small><?= htmlspecialchars((string) ($app['url'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></small></td>
                                <td><?= htmlspecialchars(strtoupper((string) ($app['type'] ?? 'external')), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) ($app['target'] ?? '_blank'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= (int) ($app['sort_order'] ?? 100) ?></td>
                                <td><?= !empty($app['is_enabled']) ? 'ON' : 'OFF' ?></td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <form method="post" action="<?= htmlspecialchars($adminBase . '/settings/apps', ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                                            <input type="hidden" name="action" value="toggle">
                                            <input type="hidden" name="app_id" value="<?= $appId ?>">
                                            <button class="btn btn-outline-secondary btn-sm" type="submit">Toggle</button>
                                        </form>
                                        <form method="post" action="<?= htmlspecialchars($adminBase . '/settings/apps', ENT_QUOTES, 'UTF-8') ?>" onsubmit="return confirm('Supprimer cette app ?')">
                                            <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="app_id" value="<?= $appId ?>">
                                            <button class="btn btn-outline-danger btn-sm" type="submit">Supprimer</button>
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
<?php
$content = (string) ob_get_clean();
require CATMIN_ADMIN . '/views/layouts/admin.php';
