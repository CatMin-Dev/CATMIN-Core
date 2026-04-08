<?php

declare(strict_types=1);

$apps = isset($apps) && is_array($apps) ? $apps : [];
$activeSettingsNav = (string) ($activeSettingsNav ?? 'apps');

$pageTitle = __('settings.title') . ' · ' . __('settings.section.apps');
$pageDescription = '';
$activeNav = $activeSettingsNav;
$breadcrumbs = [
    ['label' => __('common.admin'), 'href' => $adminBase . '/'],
    ['label' => __('nav.settings'), 'href' => $adminBase . '/settings/general'],
    ['label' => __('settings.section.apps')],
];

$csrf = htmlspecialchars((new \Core\security\CsrfManager())->token(), ENT_QUOTES, 'UTF-8');

ob_start();
?>
<section class="card">
    <div class="card-header bg-transparent border-0 pt-3">
        <h3 class="h6 mb-0"><?= htmlspecialchars(__('settings.apps.launcher_topbar'), ENT_QUOTES, 'UTF-8') ?></h3>
    </div>
    <div class="card-body pt-2">
        <form method="post" action="<?= htmlspecialchars($adminBase . '/settings/apps', ENT_QUOTES, 'UTF-8') ?>" class="row g-3 mb-4">
            <input type="hidden" name="_csrf" value="<?= $csrf ?>">
            <input type="hidden" name="action" value="create">
            <div class="col-12 col-lg-3">
                <label class="form-label"><?= htmlspecialchars(__('settings.apps.label'), ENT_QUOTES, 'UTF-8') ?></label>
                <input class="form-control" type="text" name="label" placeholder="GPT">
            </div>
            <div class="col-12 col-lg-3">
                <label class="form-label"><?= htmlspecialchars(__('settings.apps.url'), ENT_QUOTES, 'UTF-8') ?></label>
                <input class="form-control" type="text" name="url" placeholder="https://chat.openai.com">
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label"><?= htmlspecialchars(__('settings.apps.type'), ENT_QUOTES, 'UTF-8') ?></label>
                <select class="form-select" name="type">
                    <option value="external"><?= htmlspecialchars(__('settings.apps.type.external'), ENT_QUOTES, 'UTF-8') ?></option>
                    <option value="internal"><?= htmlspecialchars(__('settings.apps.type.internal'), ENT_QUOTES, 'UTF-8') ?></option>
                </select>
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label"><?= htmlspecialchars(__('settings.apps.target'), ENT_QUOTES, 'UTF-8') ?></label>
                <select class="form-select" name="target">
                    <option value="_blank"><?= htmlspecialchars(__('settings.apps.target.new_tab'), ENT_QUOTES, 'UTF-8') ?></option>
                    <option value="_self"><?= htmlspecialchars(__('settings.apps.target.same_tab'), ENT_QUOTES, 'UTF-8') ?></option>
                </select>
            </div>
            <div class="col-6 col-lg-1">
                <label class="form-label"><?= htmlspecialchars(__('settings.apps.order'), ENT_QUOTES, 'UTF-8') ?></label>
                <input class="form-control" type="number" min="1" name="sort_order" value="100">
            </div>
            <div class="col-6 col-lg-1">
                <label class="form-label"><?= htmlspecialchars(__('settings.apps.on'), ENT_QUOTES, 'UTF-8') ?></label>
                <div class="form-check form-switch mt-2">
                    <input class="form-check-input" type="checkbox" name="is_enabled" value="1" checked>
                </div>
            </div>
            <div class="col-12 col-lg-4">
                <label class="form-label"><?= htmlspecialchars(__('settings.apps.icon'), ENT_QUOTES, 'UTF-8') ?></label>
                <input class="form-control" type="text" name="icon" placeholder="bi bi-robot ou /assets/icons/app.svg">
            </div>
            <div class="col-12 col-lg-2 align-self-end">
                <button class="btn btn-primary w-100" type="submit"><?= htmlspecialchars(__('common.add'), ENT_QUOTES, 'UTF-8') ?></button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th><?= htmlspecialchars(__('settings.apps.label'), ENT_QUOTES, 'UTF-8') ?></th>
                        <th><?= htmlspecialchars(__('settings.apps.url'), ENT_QUOTES, 'UTF-8') ?></th>
                        <th><?= htmlspecialchars(__('settings.apps.type'), ENT_QUOTES, 'UTF-8') ?></th>
                        <th><?= htmlspecialchars(__('settings.apps.target'), ENT_QUOTES, 'UTF-8') ?></th>
                        <th><?= htmlspecialchars(__('settings.apps.order'), ENT_QUOTES, 'UTF-8') ?></th>
                        <th><?= htmlspecialchars(__('common.status'), ENT_QUOTES, 'UTF-8') ?></th>
                        <th class="text-end"><?= htmlspecialchars(__('common.actions'), ENT_QUOTES, 'UTF-8') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($apps === []): ?>
                        <tr><td colspan="8" class="text-body-secondary"><?= htmlspecialchars(__('settings.apps.empty'), ENT_QUOTES, 'UTF-8') ?></td></tr>
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
                                <td><?= !empty($app['is_enabled']) ? htmlspecialchars(__('settings.apps.on'), ENT_QUOTES, 'UTF-8') : htmlspecialchars(__('settings.apps.off'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <form method="post" action="<?= htmlspecialchars($adminBase . '/settings/apps', ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                                            <input type="hidden" name="action" value="toggle">
                                            <input type="hidden" name="app_id" value="<?= $appId ?>">
                                            <button class="btn btn-outline-secondary btn-sm" type="submit"><?= htmlspecialchars(__('settings.apps.toggle'), ENT_QUOTES, 'UTF-8') ?></button>
                                        </form>
                                        <form method="post" action="<?= htmlspecialchars($adminBase . '/settings/apps', ENT_QUOTES, 'UTF-8') ?>" onsubmit="return confirm('<?= htmlspecialchars(__('settings.apps.confirm_delete'), ENT_QUOTES, 'UTF-8') ?>')">
                                            <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="app_id" value="<?= $appId ?>">
                                            <button class="btn btn-outline-danger btn-sm" type="submit"><?= htmlspecialchars(__('common.delete'), ENT_QUOTES, 'UTF-8') ?></button>
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
