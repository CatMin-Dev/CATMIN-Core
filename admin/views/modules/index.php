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
$pageTitle = $isStatusView ? __('modules.title.status') : __('modules.title.manager');
$pageDescription = '';
$activeNav = $isStatusView ? 'module-status' : 'module-manager';
$breadcrumbs = [
    ['label' => 'Admin', 'href' => $adminBase . '/'],
    ['label' => __('nav.modules'), 'href' => $adminBase . '/modules'],
];
if ($isStatusView) {
    $breadcrumbs[] = ['label' => __('nav.module_status')];
}

$csrf = htmlspecialchars((new CsrfManager())->token(), ENT_QUOTES, 'UTF-8');

ob_start();
?>
<?php if ($isStatusView): ?>
    <div class="alert alert-info py-2 mb-3">
        <?= htmlspecialchars(__('modules.alert.status_view'), ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php else: ?>
    <div class="alert alert-secondary py-2 mb-3">
        <?= htmlspecialchars(__('modules.alert.manager_view'), ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<form method="post" action="<?= htmlspecialchars($adminBase . '/modules/integrity/scan', ENT_QUOTES, 'UTF-8') ?>" class="mb-3 d-flex justify-content-end">
    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
    <button class="btn btn-sm btn-outline-primary" type="submit"><?= htmlspecialchars(__('modules.action.scan_integrity'), ENT_QUOTES, 'UTF-8') ?></button>
</form>

<section class="row g-3">
    <div class="col-12 col-md-6 col-xl-3">
        <article class="card cat-module-stat-card h-100">
            <div class="card-body">
                <small class="text-body-secondary"><?= htmlspecialchars(__('modules.stats.total'), ENT_QUOTES, 'UTF-8') ?></small>
                <p class="h4 mb-0"><?= (int) ($stats['total'] ?? 0) ?></p>
            </div>
        </article>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <article class="card cat-module-stat-card h-100">
            <div class="card-body">
                <small class="text-body-secondary"><?= htmlspecialchars(__('modules.stats.active'), ENT_QUOTES, 'UTF-8') ?></small>
                <p class="h4 mb-0 text-success"><?= (int) ($stats['active'] ?? 0) ?></p>
            </div>
        </article>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <article class="card cat-module-stat-card h-100">
            <div class="card-body">
                <small class="text-body-secondary"><?= htmlspecialchars(__('modules.stats.inactive'), ENT_QUOTES, 'UTF-8') ?></small>
                <p class="h4 mb-0 text-warning"><?= (int) ($stats['inactive'] ?? 0) ?></p>
            </div>
        </article>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <article class="card cat-module-stat-card h-100">
            <div class="card-body">
                <small class="text-body-secondary"><?= htmlspecialchars(__('modules.stats.with_errors'), ENT_QUOTES, 'UTF-8') ?></small>
                <p class="h4 mb-0 text-danger"><?= (int) ($stats['errors'] ?? 0) ?></p>
            </div>
        </article>
    </div>
</section>

<form method="get" class="card mt-3">
    <div class="card-body py-3">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-lg-5">
                <label class="form-label mb-1"><?= htmlspecialchars(__('common.search'), ENT_QUOTES, 'UTF-8') ?></label>
                <input class="form-control" type="text" name="q" value="<?= htmlspecialchars((string) ($filters['q'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="<?= htmlspecialchars(__('modules.filters.search_placeholder'), ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-6 col-lg-3">
                <label class="form-label mb-1"><?= htmlspecialchars(__('common.scope'), ENT_QUOTES, 'UTF-8') ?></label>
                <select class="form-select" name="scope">
                    <option value="all"><?= htmlspecialchars(__('common.all'), ENT_QUOTES, 'UTF-8') ?></option>
                    <?php foreach ($scopes as $scope): ?>
                        <option value="<?= htmlspecialchars((string) $scope, ENT_QUOTES, 'UTF-8') ?>" <?= ((string) ($filters['scope'] ?? 'all') === (string) $scope) ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string) $scope, ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label mb-1"><?= htmlspecialchars(__('common.status'), ENT_QUOTES, 'UTF-8') ?></label>
                <select class="form-select" name="status">
                    <?php
                    $statusOptions = [
                        'all' => __('common.all'),
                        'active' => __('modules.status.active_plural'),
                        'inactive' => __('modules.status.inactive_plural'),
                        'error' => __('modules.status.errors'),
                        'issues' => __('modules.status.to_fix'),
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
                    <button class="btn btn-primary" type="submit"><?= htmlspecialchars(__('common.filter'), ENT_QUOTES, 'UTF-8') ?></button>
                    <a class="btn btn-outline-secondary" href="<?= htmlspecialchars((string) ($isStatusView ? $adminBase . '/modules/status' : $adminBase . '/modules'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(__('common.reset'), ENT_QUOTES, 'UTF-8') ?></a>
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
                <th><?= htmlspecialchars(__('common.module'), ENT_QUOTES, 'UTF-8') ?></th>
                <th><?= htmlspecialchars(__('common.version'), ENT_QUOTES, 'UTF-8') ?></th>
                <th><?= htmlspecialchars(__('modules.table.dependencies'), ENT_QUOTES, 'UTF-8') ?></th>
                <th><?= htmlspecialchars(__('modules.table.integrity'), ENT_QUOTES, 'UTF-8') ?></th>
                <th><?= htmlspecialchars(__('modules.table.signature'), ENT_QUOTES, 'UTF-8') ?></th>
                <th><?= htmlspecialchars(__('modules.table.trust'), ENT_QUOTES, 'UTF-8') ?></th>
                <th><?= htmlspecialchars(__('modules.table.state'), ENT_QUOTES, 'UTF-8') ?></th>
                <th><?= htmlspecialchars(__('modules.table.errors'), ENT_QUOTES, 'UTF-8') ?></th>
                <th class="text-end"><?= htmlspecialchars($isStatusView ? __('modules.table.diagnostic') : __('modules.table.activation'), ENT_QUOTES, 'UTF-8') ?></th>
            </tr>
            </thead>
            <tbody>
            <?php if ($rows === []): ?>
                <tr>
                    <td colspan="9" class="text-center py-5 text-body-secondary"><?= htmlspecialchars(__('modules.table.empty'), ENT_QUOTES, 'UTF-8') ?></td>
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
                    $integrityStatus = strtolower((string) ($row['integrity_status'] ?? 'unknown'));
                    $signatureStatus = strtolower((string) ($row['signature_status'] ?? 'unknown'));
                    $trusted = (bool) ($row['trusted'] ?? false);
                    ?>
                    <tr>
                        <td>
                            <p class="mb-0 fw-semibold"><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></p>
                            <small class="text-body-secondary"><?= htmlspecialchars($scope . '/' . $slug, ENT_QUOTES, 'UTF-8') ?></small>
                        </td>
                        <td><span class="badge text-bg-light border"><?= htmlspecialchars($version, ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td>
                            <?php if ($dependencies === []): ?>
                                <small class="text-body-secondary"><?= htmlspecialchars(__('common.none_feminine'), ENT_QUOTES, 'UTF-8') ?></small>
                            <?php else: ?>
                                <div class="d-flex flex-wrap gap-1">
                                    <?php foreach ($dependencies as $dep): ?>
                                        <span class="badge text-bg-secondary"><?= htmlspecialchars((string) $dep, ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $integrityBadge = match ($integrityStatus) {
                                'valid' => 'text-bg-success',
                                'tampered', 'invalid' => 'text-bg-danger',
                                'missing_checksums', 'unsupported_schema' => 'text-bg-warning',
                                default => 'text-bg-secondary',
                            };
                            ?>
                            <span class="badge <?= $integrityBadge ?>"><?= htmlspecialchars($integrityStatus !== '' ? $integrityStatus : '-', ENT_QUOTES, 'UTF-8') ?></span>
                        </td>
                        <td>
                            <?php
                            $signatureBadge = match ($signatureStatus) {
                                'signed_valid' => 'text-bg-success',
                                'unknown_key' => 'text-bg-warning',
                                'unsigned' => 'text-bg-secondary',
                                default => 'text-bg-danger',
                            };
                            ?>
                            <span class="badge <?= $signatureBadge ?>"><?= htmlspecialchars($signatureStatus !== '' ? $signatureStatus : '-', ENT_QUOTES, 'UTF-8') ?></span>
                        </td>
                        <td>
                            <span class="badge <?= $trusted ? 'text-bg-success' : 'text-bg-danger' ?>">
                                <?= htmlspecialchars($trusted ? __('common.trusted') : __('common.not_trusted'), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($enabled): ?>
                                <span class="badge text-bg-success"><?= htmlspecialchars(__('common.active'), ENT_QUOTES, 'UTF-8') ?></span>
                            <?php else: ?>
                                <span class="badge text-bg-warning"><?= htmlspecialchars(__('common.inactive'), ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($errors === []): ?>
                                <span class="badge text-bg-success"><?= htmlspecialchars(__('common.ok'), ENT_QUOTES, 'UTF-8') ?></span>
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
                                    <span class="badge text-bg-danger"><?= htmlspecialchars(__('modules.table.action_required'), ENT_QUOTES, 'UTF-8') ?></span>
                                <?php elseif ($enabled): ?>
                                    <span class="badge text-bg-success"><?= htmlspecialchars(__('modules.table.healthy'), ENT_QUOTES, 'UTF-8') ?></span>
                                <?php else: ?>
                                    <span class="badge text-bg-warning"><?= htmlspecialchars(__('common.inactive'), ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            <?php else: ?>
                                <form method="post" action="<?= htmlspecialchars($adminBase . '/modules/toggle', ENT_QUOTES, 'UTF-8') ?>" class="d-inline">
                                    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                                    <input type="hidden" name="scope" value="<?= htmlspecialchars($scope, ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="slug" value="<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="target" value="<?= $enabled ? '0' : '1' ?>">
                                    <input type="hidden" name="return_to" value="<?= $isStatusView ? 'status' : 'manager' ?>">
                                    <button class="btn btn-sm <?= $enabled ? 'btn-outline-danger' : 'btn-outline-success' ?>" type="submit">
                                        <?= htmlspecialchars($enabled ? __('common.disable') : __('common.enable'), ENT_QUOTES, 'UTF-8') ?>
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
