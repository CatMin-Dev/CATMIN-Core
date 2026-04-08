<?php

declare(strict_types=1);

$pageTitle = __('update.title');
$pageDescription = '';
$activeNav = 'core-update';
$breadcrumbs = [
    ['label' => __('common.admin'), 'href' => $adminBase . '/'],
    ['label' => __('nav.system'), 'href' => $adminBase . '/system/monitoring'],
    ['label' => __('update.title')],
];

use Core\security\CsrfManager;

$check = is_array($check ?? null) ? $check : [];
$report = is_array($report ?? null) ? $report : [];
$steps = (array) ($report['steps'] ?? []);
$release = is_array($check['release'] ?? null) ? $check['release'] : [];
$asset = is_array($check['asset'] ?? null) ? $check['asset'] : null;
$remoteVersion = (string) ($check['remote_version'] ?? '-');
$localVersion = (string) ($check['local_version'] ?? '-');

$csrf = htmlspecialchars((new CsrfManager())->token(), ENT_QUOTES, 'UTF-8');

ob_start();
?>
<section class="card mb-3">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-lg-3">
                <small class="text-body-secondary d-block"><?= htmlspecialchars(__('update.local_version'), ENT_QUOTES, 'UTF-8') ?></small>
                <strong><?= htmlspecialchars($localVersion, ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
            <div class="col-12 col-lg-3">
                <small class="text-body-secondary d-block"><?= htmlspecialchars(__('update.remote_version'), ENT_QUOTES, 'UTF-8') ?></small>
                <strong><?= htmlspecialchars($remoteVersion !== '' ? $remoteVersion : '-', ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
            <div class="col-12 col-lg-3">
                <small class="text-body-secondary d-block"><?= htmlspecialchars(__('common.status'), ENT_QUOTES, 'UTF-8') ?></small>
                <?php if ((bool) ($check['ok'] ?? false)): ?>
                    <?php if ((bool) ($check['update_available'] ?? false)): ?>
                        <span class="badge text-bg-warning"><?= htmlspecialchars(__('update.status.available'), ENT_QUOTES, 'UTF-8') ?></span>
                    <?php else: ?>
                        <span class="badge text-bg-success"><?= htmlspecialchars(__('update.status.uptodate'), ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="badge text-bg-danger"><?= htmlspecialchars(__('update.status.error'), ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
            </div>
            <div class="col-12 col-lg-3 text-lg-end">
                <form method="post" action="<?= htmlspecialchars($adminBase . '/system/update/check', ENT_QUOTES, 'UTF-8') ?>" class="d-inline-block">
                    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                    <button type="submit" class="btn btn-outline-secondary"><?= htmlspecialchars(__('update.action.refresh'), ENT_QUOTES, 'UTF-8') ?></button>
                </form>
            </div>
        </div>
    </div>
</section>

<section class="card mb-3">
    <div class="card-header bg-transparent border-0 pt-3">
        <h3 class="h6 mb-0"><?= htmlspecialchars(__('update.release_notes'), ENT_QUOTES, 'UTF-8') ?></h3>
    </div>
    <div class="card-body pt-2">
        <p class="mb-2">
            <strong><?= htmlspecialchars((string) ($release['name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></strong>
            <?php if (($release['published_at'] ?? '') !== ''): ?>
                <small class="text-body-secondary">· <?= htmlspecialchars((string) $release['published_at'], ENT_QUOTES, 'UTF-8') ?></small>
            <?php endif; ?>
        </p>
        <pre class="small border rounded p-3 mb-0 bg-body-tertiary" style="max-height: 260px; overflow:auto;"><?= htmlspecialchars((string) ($release['body'] ?? __('update.release_notes_empty')), ENT_QUOTES, 'UTF-8') ?></pre>
    </div>
</section>

<section class="card">
    <div class="card-body">
        <div class="d-flex flex-wrap gap-2">
            <form method="post" action="<?= htmlspecialchars($adminBase . '/system/update/dry-run', ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                <button type="submit" class="btn btn-outline-primary" <?= !$asset ? 'disabled' : '' ?>><?= htmlspecialchars(__('update.action.dry_run'), ENT_QUOTES, 'UTF-8') ?></button>
            </form>
            <form method="post" action="<?= htmlspecialchars($adminBase . '/system/update/run', ENT_QUOTES, 'UTF-8') ?>" onsubmit="return confirm('<?= htmlspecialchars(__('update.confirm_run'), ENT_QUOTES, 'UTF-8') ?>');">
                <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                <button type="submit" class="btn btn-primary" <?= !((bool) ($check['update_available'] ?? false)) ? 'disabled' : '' ?>><?= htmlspecialchars(__('update.action.run'), ENT_QUOTES, 'UTF-8') ?></button>
            </form>
        </div>
        <?php if ($asset): ?>
            <p class="small text-body-secondary mt-3 mb-0">
                <?= htmlspecialchars(__('update.asset'), ENT_QUOTES, 'UTF-8') ?>:
                <strong><?= htmlspecialchars((string) ($asset['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
            </p>
        <?php endif; ?>
    </div>
</section>

<?php if ($report !== []): ?>
    <section class="card mt-3">
        <div class="card-header bg-transparent border-0 pt-3">
            <h3 class="h6 mb-0"><?= htmlspecialchars(__('update.last_report'), ENT_QUOTES, 'UTF-8') ?></h3>
        </div>
        <div class="card-body pt-2">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                    <tr>
                        <th><?= htmlspecialchars(__('common.step'), ENT_QUOTES, 'UTF-8') ?></th>
                        <th><?= htmlspecialchars(__('common.status'), ENT_QUOTES, 'UTF-8') ?></th>
                        <th><?= htmlspecialchars(__('common.message'), ENT_QUOTES, 'UTF-8') ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($steps as $step): ?>
                        <?php
                        $status = strtolower((string) ($step['status'] ?? 'unknown'));
                        $badge = match ($status) {
                            'ok' => 'text-bg-success',
                            'warning' => 'text-bg-warning',
                            'error' => 'text-bg-danger',
                            default => 'text-bg-secondary',
                        };
                        ?>
                        <tr>
                            <td><?= htmlspecialchars((string) ($step['name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><span class="badge <?= $badge ?>"><?= htmlspecialchars($status !== '' ? $status : '-', ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td><?= htmlspecialchars((string) ($step['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
<?php endif; ?>
<?php
$content = (string) ob_get_clean();
require CATMIN_ADMIN . '/views/layouts/admin.php';

