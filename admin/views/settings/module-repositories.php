<?php

declare(strict_types=1);

use Core\security\CsrfManager;

$repositories = isset($repositories) && is_array($repositories) ? $repositories : [];
$policy = isset($policy) && is_array($policy) ? $policy : [];
$activeSettingsNav = (string) ($activeSettingsNav ?? 'module-repositories');

$pageTitle = __('settings.title') . ' · ' . __('settings.section.module_repositories');
$pageDescription = '';
$activeNav = $activeSettingsNav;
$breadcrumbs = [
    ['label' => __('common.admin'), 'href' => $adminBase . '/'],
    ['label' => __('nav.settings'), 'href' => $adminBase . '/settings/general'],
    ['label' => __('settings.section.module_repositories')],
];
$csrf = htmlspecialchars((new CsrfManager())->token(), ENT_QUOTES, 'UTF-8');

$editId = (int) ($_GET['edit'] ?? 0);
$editRow = null;
foreach ($repositories as $repo) {
    if ((int) ($repo['id'] ?? 0) === $editId) {
        $editRow = $repo;
        break;
    }
}

ob_start();
?>
<section class="card mb-3">
    <div class="card-header bg-transparent border-0 pt-3">
        <h3 class="h6 mb-0"><?= htmlspecialchars(__('settings.module_repositories.policies'), ENT_QUOTES, 'UTF-8') ?></h3>
    </div>
    <div class="card-body pt-2">
        <form method="post" action="<?= htmlspecialchars($adminBase . '/settings/module-repositories', ENT_QUOTES, 'UTF-8') ?>" class="row g-3">
            <input type="hidden" name="_csrf" value="<?= $csrf ?>">
            <input type="hidden" name="action" value="save_policy">

            <?php
            $policyOptions = [
                'allow_official', 'allow_trusted', 'allow_community',
                'require_checksums_official', 'require_checksums_trusted', 'require_checksums_community',
                'require_signature_official', 'require_signature_trusted', 'require_signature_community',
                'hide_unverified_modules', 'show_community_by_default',
            ];
            foreach ($policyOptions as $key):
                ?>
                <div class="col-12 col-lg-4">
                    <label class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" value="1" <?= !empty($policy[$key]) ? 'checked' : '' ?>>
                        <span class="form-check-label"><?= htmlspecialchars(__('settings.module_repositories.policy.' . $key), ENT_QUOTES, 'UTF-8') ?></span>
                    </label>
                </div>
            <?php endforeach; ?>

            <div class="col-12">
                <button type="submit" class="btn btn-primary"><?= htmlspecialchars(__('common.save'), ENT_QUOTES, 'UTF-8') ?></button>
            </div>
        </form>
    </div>
</section>

<section class="card mb-3">
    <div class="card-header bg-transparent border-0 pt-3">
        <h3 class="h6 mb-0"><?= htmlspecialchars($editRow ? __('settings.module_repositories.edit_title') : __('settings.module_repositories.add_title'), ENT_QUOTES, 'UTF-8') ?></h3>
    </div>
    <div class="card-body pt-2">
        <form method="post" action="<?= htmlspecialchars($adminBase . '/settings/module-repositories', ENT_QUOTES, 'UTF-8') ?>" class="row g-3">
            <input type="hidden" name="_csrf" value="<?= $csrf ?>">
            <input type="hidden" name="action" value="<?= $editRow ? 'update' : 'create' ?>">
            <?php if ($editRow): ?>
                <input type="hidden" name="repository_id" value="<?= (int) ($editRow['id'] ?? 0) ?>">
            <?php endif; ?>

            <div class="col-12 col-lg-3">
                <label class="form-label"><?= htmlspecialchars(__('common.name'), ENT_QUOTES, 'UTF-8') ?></label>
                <input class="form-control" type="text" name="name" value="<?= htmlspecialchars((string) ($editRow['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
            <div class="col-12 col-lg-2">
                <label class="form-label"><?= htmlspecialchars(__('common.slug'), ENT_QUOTES, 'UTF-8') ?></label>
                <input class="form-control" type="text" name="slug" value="<?= htmlspecialchars((string) ($editRow['slug'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
            <div class="col-12 col-lg-2">
                <label class="form-label"><?= htmlspecialchars(__('settings.module_repositories.provider'), ENT_QUOTES, 'UTF-8') ?></label>
                <?php $provider = strtolower((string) ($editRow['provider'] ?? 'github')); ?>
                <select class="form-select" name="provider">
                    <option value="github" <?= $provider === 'github' ? 'selected' : '' ?>>GitHub</option>
                    <option value="custom_http_index" <?= $provider === 'custom_http_index' ? 'selected' : '' ?>>Custom HTTP Index</option>
                </select>
            </div>
            <div class="col-12 col-lg-2">
                <label class="form-label"><?= htmlspecialchars(__('settings.module_repositories.trust_level'), ENT_QUOTES, 'UTF-8') ?></label>
                <?php $trustLevel = strtolower((string) ($editRow['trust_level'] ?? 'community')); ?>
                <select class="form-select" name="trust_level">
                    <?php foreach (['official', 'trusted', 'community', 'blocked'] as $level): ?>
                        <option value="<?= htmlspecialchars($level, ENT_QUOTES, 'UTF-8') ?>" <?= $trustLevel === $level ? 'selected' : '' ?>><?= htmlspecialchars(__('common.' . $level), ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-lg-3">
                <label class="form-label"><?= htmlspecialchars(__('settings.module_repositories.branch_channel'), ENT_QUOTES, 'UTF-8') ?></label>
                <input class="form-control" type="text" name="branch_or_channel" value="<?= htmlspecialchars((string) ($editRow['branch_or_channel'] ?? 'main'), ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="col-12 col-lg-6">
                <label class="form-label"><?= htmlspecialchars(__('settings.module_repositories.repo_url'), ENT_QUOTES, 'UTF-8') ?></label>
                <input class="form-control" type="text" name="repo_url" value="<?= htmlspecialchars((string) ($editRow['repo_url'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
            <div class="col-12 col-lg-3">
                <label class="form-label"><?= htmlspecialchars(__('settings.module_repositories.api_url'), ENT_QUOTES, 'UTF-8') ?></label>
                <input class="form-control" type="text" name="api_url" value="<?= htmlspecialchars((string) ($editRow['api_url'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-12 col-lg-3">
                <label class="form-label"><?= htmlspecialchars(__('settings.module_repositories.index_url'), ENT_QUOTES, 'UTF-8') ?></label>
                <input class="form-control" type="text" name="index_url" value="<?= htmlspecialchars((string) ($editRow['index_url'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="col-12 col-lg-6">
                <label class="form-label"><?= htmlspecialchars(__('settings.module_repositories.allowed_channels'), ENT_QUOTES, 'UTF-8') ?></label>
                <input class="form-control" type="text" name="allowed_release_channels" value="<?= htmlspecialchars((string) ($editRow['allowed_release_channels'] ?? 'stable,beta,dev'), ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-12 col-lg-6">
                <label class="form-label"><?= htmlspecialchars(__('common.description'), ENT_QUOTES, 'UTF-8') ?></label>
                <input class="form-control" type="text" name="notes" value="<?= htmlspecialchars((string) ($editRow['notes'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="col-12 col-lg-3">
                <label class="form-check form-switch mt-4">
                    <input class="form-check-input" type="checkbox" name="is_enabled" value="1" <?= !array_key_exists('is_enabled', (array) $editRow) || !empty($editRow['is_enabled']) ? 'checked' : '' ?>>
                    <span class="form-check-label"><?= htmlspecialchars(__('common.active'), ENT_QUOTES, 'UTF-8') ?></span>
                </label>
            </div>
            <div class="col-12 col-lg-3">
                <label class="form-check form-switch mt-4">
                    <input class="form-check-input" type="checkbox" name="is_official" value="1" <?= !empty($editRow['is_official']) ? 'checked' : '' ?>>
                    <span class="form-check-label"><?= htmlspecialchars(__('common.official'), ENT_QUOTES, 'UTF-8') ?></span>
                </label>
            </div>
            <div class="col-12 col-lg-2">
                <label class="form-check form-switch mt-4">
                    <input class="form-check-input" type="checkbox" name="requires_signature" value="1" <?= !empty($editRow['requires_signature']) ? 'checked' : '' ?>>
                    <span class="form-check-label">Signature</span>
                </label>
            </div>
            <div class="col-12 col-lg-2">
                <label class="form-check form-switch mt-4">
                    <input class="form-check-input" type="checkbox" name="requires_checksums" value="1" <?= !empty($editRow['requires_checksums']) ? 'checked' : '' ?>>
                    <span class="form-check-label">Checksums</span>
                </label>
            </div>
            <div class="col-12 col-lg-2">
                <label class="form-check form-switch mt-4">
                    <input class="form-check-input" type="checkbox" name="requires_manifest_standard" value="1" <?= !array_key_exists('requires_manifest_standard', (array) $editRow) || !empty($editRow['requires_manifest_standard']) ? 'checked' : '' ?>>
                    <span class="form-check-label">Manifest std</span>
                </label>
            </div>

            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><?= htmlspecialchars($editRow ? __('common.save') : __('common.add'), ENT_QUOTES, 'UTF-8') ?></button>
                <?php if ($editRow): ?>
                    <a href="<?= htmlspecialchars($adminBase . '/settings/module-repositories', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary"><?= htmlspecialchars(__('common.cancel'), ENT_QUOTES, 'UTF-8') ?></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</section>

<section class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
            <tr>
                <th>ID</th>
                <th><?= htmlspecialchars(__('common.name'), ENT_QUOTES, 'UTF-8') ?></th>
                <th><?= htmlspecialchars(__('settings.module_repositories.provider'), ENT_QUOTES, 'UTF-8') ?></th>
                <th><?= htmlspecialchars(__('settings.module_repositories.trust_level'), ENT_QUOTES, 'UTF-8') ?></th>
                <th><?= htmlspecialchars(__('common.status'), ENT_QUOTES, 'UTF-8') ?></th>
                <th><?= htmlspecialchars(__('settings.module_repositories.last_check'), ENT_QUOTES, 'UTF-8') ?></th>
                <th class="text-end"><?= htmlspecialchars(__('common.actions'), ENT_QUOTES, 'UTF-8') ?></th>
            </tr>
            </thead>
            <tbody>
            <?php if ($repositories === []): ?>
                <tr>
                    <td colspan="7" class="text-center py-4 text-body-secondary"><?= htmlspecialchars(__('settings.module_repositories.empty'), ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
            <?php endif; ?>
            <?php foreach ($repositories as $repo): ?>
                <?php
                $id = (int) ($repo['id'] ?? 0);
                $trustLevel = strtolower((string) ($repo['trust_level'] ?? 'community'));
                $badgeClass = $trustLevel === 'official' ? 'text-bg-success' : ($trustLevel === 'trusted' ? 'text-bg-info' : ($trustLevel === 'blocked' ? 'text-bg-danger' : 'text-bg-warning'));
                ?>
                <tr>
                    <td><?= $id ?></td>
                    <td>
                        <div class="fw-semibold"><?= htmlspecialchars((string) ($repo['name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                        <small class="text-body-secondary"><?= htmlspecialchars((string) ($repo['slug'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></small>
                    </td>
                    <td><?= htmlspecialchars((string) ($repo['provider'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars(__('common.' . $trustLevel), ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td>
                        <?php if (!empty($repo['is_enabled'])): ?>
                            <span class="badge text-bg-success"><?= htmlspecialchars(__('common.active'), ENT_QUOTES, 'UTF-8') ?></span>
                        <?php else: ?>
                            <span class="badge text-bg-secondary"><?= htmlspecialchars(__('common.inactive'), ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div><?= htmlspecialchars((string) ($repo['last_check_status'] ?? 'never'), ENT_QUOTES, 'UTF-8') ?></div>
                        <small class="text-body-secondary"><?= htmlspecialchars((string) ($repo['last_check_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></small>
                    </td>
                    <td class="text-end">
                        <div class="d-inline-flex gap-1 flex-wrap justify-content-end">
                            <a href="<?= htmlspecialchars($adminBase . '/settings/module-repositories?edit=' . $id, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-secondary"><?= htmlspecialchars(__('common.edit'), ENT_QUOTES, 'UTF-8') ?></a>
                            <form method="post" action="<?= htmlspecialchars($adminBase . '/settings/module-repositories', ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                                <input type="hidden" name="action" value="check">
                                <input type="hidden" name="repository_id" value="<?= $id ?>">
                                <button class="btn btn-sm btn-outline-primary" type="submit"><?= htmlspecialchars(__('settings.module_repositories.check'), ENT_QUOTES, 'UTF-8') ?></button>
                            </form>
                            <form method="post" action="<?= htmlspecialchars($adminBase . '/settings/module-repositories', ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="repository_id" value="<?= $id ?>">
                                <button class="btn btn-sm btn-outline-secondary" type="submit"><?= !empty($repo['is_enabled']) ? htmlspecialchars(__('common.disable'), ENT_QUOTES, 'UTF-8') : htmlspecialchars(__('common.enable'), ENT_QUOTES, 'UTF-8') ?></button>
                            </form>
                            <?php if ($trustLevel !== 'blocked'): ?>
                                <form method="post" action="<?= htmlspecialchars($adminBase . '/settings/module-repositories', ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                                    <input type="hidden" name="action" value="block">
                                    <input type="hidden" name="repository_id" value="<?= $id ?>">
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Block</button>
                                </form>
                            <?php endif; ?>
                            <?php if (empty($repo['is_official'])): ?>
                                <form method="post" action="<?= htmlspecialchars($adminBase . '/settings/module-repositories', ENT_QUOTES, 'UTF-8') ?>" onsubmit="return confirm('<?= htmlspecialchars(__('settings.module_repositories.confirm_delete'), ENT_QUOTES, 'UTF-8') ?>')">
                                    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="repository_id" value="<?= $id ?>">
                                    <button class="btn btn-sm btn-outline-danger" type="submit"><?= htmlspecialchars(__('common.delete'), ENT_QUOTES, 'UTF-8') ?></button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
$content = (string) ob_get_clean();
require CATMIN_ADMIN . '/views/layouts/admin.php';
