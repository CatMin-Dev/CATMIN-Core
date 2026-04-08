<?php

declare(strict_types=1);

$filters = isset($filters) && is_array($filters) ? $filters : [];
$systemLogs = isset($systemLogs) && is_array($systemLogs) ? $systemLogs : [];
$securityLogs = isset($securityLogs) && is_array($securityLogs) ? $securityLogs : [];
$adminActivity = isset($adminActivity) && is_array($adminActivity) ? $adminActivity : [];

$source = (string) ($filters['source'] ?? 'all');

$pageTitle = __('logs.title');
$pageDescription = '';
$activeNav = 'logs';
$breadcrumbs = [
    ['label' => __('common.admin'), 'href' => $adminBase . '/'],
    ['label' => __('logs.title')],
];

ob_start();
?>
<form method="get" class="card mb-3">
    <div class="card-body py-3">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-lg-3">
                <label class="form-label mb-1"><?= htmlspecialchars(__('logs.source'), ENT_QUOTES, 'UTF-8') ?></label>
                <select class="form-select" name="source">
                    <?php foreach (['all' => __('common.all'), 'system' => __('logs.source.system'), 'security' => __('logs.source.security'), 'admin' => __('logs.source.admin')] as $value => $label): ?>
                        <option value="<?= $value ?>" <?= $source === $value ? 'selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-lg-3">
                <label class="form-label mb-1"><?= htmlspecialchars(__('logs.level'), ENT_QUOTES, 'UTF-8') ?></label>
                <select class="form-select" name="level">
                    <?php foreach (['ALL', 'INFO', 'WARN', 'ERROR'] as $lvl): ?>
                        <option value="<?= $lvl ?>" <?= ((string) ($filters['level'] ?? 'ALL') === $lvl) ? 'selected' : '' ?>><?= $lvl ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-lg-4">
                <label class="form-label mb-1"><?= htmlspecialchars(__('common.search'), ENT_QUOTES, 'UTF-8') ?></label>
                <input class="form-control" type="text" name="q" value="<?= htmlspecialchars((string) ($filters['q'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="<?= htmlspecialchars(__('logs.search_placeholder'), ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-12 col-lg-2">
                <div class="d-grid gap-2">
                    <button class="btn btn-primary" type="submit"><?= htmlspecialchars(__('common.filter'), ENT_QUOTES, 'UTF-8') ?></button>
                    <a class="btn btn-outline-secondary" href="<?= htmlspecialchars((string) ($adminBase . '/logs'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(__('common.reset'), ENT_QUOTES, 'UTF-8') ?></a>
                </div>
            </div>
        </div>
    </div>
</form>

<?php if ($source === 'all' || $source === 'system'): ?>
    <section class="card mb-3">
        <div class="card-header bg-transparent border-0 pt-3">
            <h3 class="h6 mb-0"><?= htmlspecialchars(__('logs.section.system'), ENT_QUOTES, 'UTF-8') ?></h3>
        </div>
        <div class="card-body pt-2">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                    <tr><th><?= htmlspecialchars(__('logs.table.date'), ENT_QUOTES, 'UTF-8') ?></th><th><?= htmlspecialchars(__('logs.table.level'), ENT_QUOTES, 'UTF-8') ?></th><th><?= htmlspecialchars(__('logs.table.message'), ENT_QUOTES, 'UTF-8') ?></th></tr>
                    </thead>
                    <tbody>
                    <?php if ($systemLogs === []): ?>
                        <tr><td colspan="3" class="text-body-secondary"><?= htmlspecialchars(__('logs.empty.system'), ENT_QUOTES, 'UTF-8') ?></td></tr>
                    <?php else: ?>
                        <?php foreach ($systemLogs as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars((string) ($row['date'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><span class="badge text-bg-secondary"><?= htmlspecialchars((string) ($row['level'] ?? 'INFO'), ENT_QUOTES, 'UTF-8') ?></span></td>
                                <td><code><?= htmlspecialchars((string) ($row['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?></code></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php if ($source === 'all' || $source === 'security'): ?>
    <section class="card mb-3">
        <div class="card-header bg-transparent border-0 pt-3">
            <h3 class="h6 mb-0"><?= htmlspecialchars(__('logs.section.security'), ENT_QUOTES, 'UTF-8') ?></h3>
        </div>
        <div class="card-body pt-2">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                    <tr><th><?= htmlspecialchars(__('logs.table.date'), ENT_QUOTES, 'UTF-8') ?></th><th><?= htmlspecialchars(__('logs.table.event'), ENT_QUOTES, 'UTF-8') ?></th><th><?= htmlspecialchars(__('logs.table.severity'), ENT_QUOTES, 'UTF-8') ?></th><th><?= htmlspecialchars(__('logs.table.ip'), ENT_QUOTES, 'UTF-8') ?></th><th><?= htmlspecialchars(__('logs.table.message'), ENT_QUOTES, 'UTF-8') ?></th></tr>
                    </thead>
                    <tbody>
                    <?php if ($securityLogs === []): ?>
                        <tr><td colspan="5" class="text-body-secondary"><?= htmlspecialchars(__('logs.empty.security'), ENT_QUOTES, 'UTF-8') ?></td></tr>
                    <?php else: ?>
                        <?php foreach ($securityLogs as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars((string) ($row['created_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) ($row['event_type'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><span class="badge text-bg-warning"><?= htmlspecialchars((string) ($row['severity'] ?? 'info'), ENT_QUOTES, 'UTF-8') ?></span></td>
                                <td><code><?= htmlspecialchars((string) ($row['ip_address'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></code></td>
                                <td><?= htmlspecialchars((string) ($row['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php if ($source === 'all' || $source === 'admin'): ?>
    <section class="card">
        <div class="card-header bg-transparent border-0 pt-3">
            <h3 class="h6 mb-0"><?= htmlspecialchars(__('logs.section.admin_activity'), ENT_QUOTES, 'UTF-8') ?></h3>
        </div>
        <div class="card-body pt-2">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                    <tr><th><?= htmlspecialchars(__('logs.table.user'), ENT_QUOTES, 'UTF-8') ?></th><th><?= htmlspecialchars(__('common.email'), ENT_QUOTES, 'UTF-8') ?></th><th><?= htmlspecialchars(__('common.last_login'), ENT_QUOTES, 'UTF-8') ?></th><th><?= htmlspecialchars(__('common.updated_at'), ENT_QUOTES, 'UTF-8') ?></th><th><?= htmlspecialchars(__('common.status'), ENT_QUOTES, 'UTF-8') ?></th></tr>
                    </thead>
                    <tbody>
                    <?php if ($adminActivity === []): ?>
                        <tr><td colspan="5" class="text-body-secondary"><?= htmlspecialchars(__('logs.empty.admin'), ENT_QUOTES, 'UTF-8') ?></td></tr>
                    <?php else: ?>
                        <?php foreach ($adminActivity as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars((string) ($row['username'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) ($row['email'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) ($row['last_login_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) ($row['updated_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <?php if (((int) ($row['is_active'] ?? 0)) === 1): ?>
                                        <span class="badge text-bg-success"><?= htmlspecialchars(__('common.active'), ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php else: ?>
                                        <span class="badge text-bg-secondary"><?= htmlspecialchars(__('common.inactive'), ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
<?php endif; ?>
<?php
$content = (string) ob_get_clean();
require CATMIN_ADMIN . '/views/layouts/admin.php';
