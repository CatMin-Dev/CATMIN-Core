<?php

declare(strict_types=1);

$pageTitle = __('trust.title');
$pageDescription = '';
$activeNav = 'trust-center';
$breadcrumbs = [
    ['label' => __('common.admin'), 'href' => $adminBase . '/'],
    ['label' => __('nav.system'), 'href' => $adminBase . '/system/monitoring'],
    ['label' => __('nav.trust_center')],
];

$snapshot = is_array($snapshot ?? null) ? $snapshot : [];
$stats = (array) ($snapshot['stats'] ?? []);
$sources = (array) ($snapshot['sources'] ?? []);
$groups = (array) ($snapshot['groups'] ?? []);
$publishers = (array) ($snapshot['publishers'] ?? []);

ob_start();
?>
<section class="card mb-3">
    <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <h3 class="h6 mb-1"><?= htmlspecialchars(__('trust.summary.title'), ENT_QUOTES, 'UTF-8') ?></h3>
            <p class="mb-0 text-body-secondary"><?= htmlspecialchars(__('trust.summary.subtitle'), ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <span class="badge text-bg-dark"><?= htmlspecialchars(strtoupper((string) ($snapshot['mode'] ?? 'local_only')), ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <div class="card-body pt-0">
        <div class="row g-2">
            <div class="col-6 col-lg-2"><div class="border rounded p-2 small"><?= htmlspecialchars(__('trust.summary.total'), ENT_QUOTES, 'UTF-8') ?>: <strong><?= (int) ($stats['total'] ?? 0) ?></strong></div></div>
            <div class="col-6 col-lg-2"><div class="border rounded p-2 small"><?= htmlspecialchars(__('trust.scope.official'), ENT_QUOTES, 'UTF-8') ?>: <strong><?= (int) ($stats['official'] ?? 0) ?></strong></div></div>
            <div class="col-6 col-lg-2"><div class="border rounded p-2 small"><?= htmlspecialchars(__('trust.scope.trusted'), ENT_QUOTES, 'UTF-8') ?>: <strong><?= (int) ($stats['trusted'] ?? 0) ?></strong></div></div>
            <div class="col-6 col-lg-2"><div class="border rounded p-2 small"><?= htmlspecialchars(__('trust.scope.community'), ENT_QUOTES, 'UTF-8') ?>: <strong><?= (int) ($stats['community'] ?? 0) ?></strong></div></div>
            <div class="col-6 col-lg-2"><div class="border rounded p-2 small"><?= htmlspecialchars(__('trust.scope.local_only'), ENT_QUOTES, 'UTF-8') ?>: <strong><?= (int) ($stats['local_only'] ?? 0) ?></strong></div></div>
            <div class="col-6 col-lg-2"><div class="border rounded p-2 small"><?= htmlspecialchars(__('trust.scope.revoked'), ENT_QUOTES, 'UTF-8') ?>: <strong><?= (int) ($stats['revoked'] ?? 0) ?></strong></div></div>
        </div>
        <div class="small text-body-secondary mt-2">
            <?= htmlspecialchars(__('trust.summary.last_sync'), ENT_QUOTES, 'UTF-8') ?>:
            <strong><?= htmlspecialchars((string) (($snapshot['last_sync_at'] ?? '') !== '' ? $snapshot['last_sync_at'] : __('trust.summary.never')), ENT_QUOTES, 'UTF-8') ?></strong>
            · <?= htmlspecialchars((string) ($snapshot['last_sync_message'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
        </div>
    </div>
</section>

<?php require __DIR__ . '/partials/trust-sources.php'; ?>
<?php require __DIR__ . '/partials/local-keys.php'; ?>
<?php require __DIR__ . '/partials/keyring-table.php'; ?>

<section class="card">
    <div class="card-header bg-transparent border-0 pt-3">
        <h3 class="h6 mb-0"><?= htmlspecialchars(__('trust.publishers.title'), ENT_QUOTES, 'UTF-8') ?></h3>
    </div>
    <div class="card-body pt-2">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th><?= htmlspecialchars(__('trust.publishers.publisher'), ENT_QUOTES, 'UTF-8') ?></th>
                        <th><?= htmlspecialchars(__('common.scope'), ENT_QUOTES, 'UTF-8') ?></th>
                        <th><?= htmlspecialchars(__('trust.keys.source'), ENT_QUOTES, 'UTF-8') ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($publishers === []): ?>
                    <tr><td colspan="3" class="text-body-secondary small"><?= htmlspecialchars(__('trust.publishers.empty'), ENT_QUOTES, 'UTF-8') ?></td></tr>
                <?php else: ?>
                    <?php foreach ($publishers as $publisher): ?>
                        <tr>
                            <td><code><?= htmlspecialchars((string) ($publisher['publisher'] ?? ''), ENT_QUOTES, 'UTF-8') ?></code></td>
                            <td><?= htmlspecialchars((string) ($publisher['trust_scope'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) ($publisher['source'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
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
