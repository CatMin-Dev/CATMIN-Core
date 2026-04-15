<?php

declare(strict_types=1);

$pageTitle = __('maintenance.read.title');
$pageDescription = '';
$activeNav = 'maintenance';
$breadcrumbs = [
    ['label' => __('common.admin'), 'href' => $adminBase . '/'],
    ['label' => __('maintenance.title'), 'href' => $adminBase . '/maintenance'],
    ['label' => (string) ($backupName ?? __('maintenance.backup_default'))],
];

$backupName = (string) ($backupName ?? '-');
$backupPath = (string) ($backupPath ?? '-');
$backupSize = (int) ($backupSize ?? 0);
$backupManifest = isset($backupManifest) && is_array($backupManifest) ? $backupManifest : [];
$backupDetails = isset($backupDetails) && is_array($backupDetails) ? $backupDetails : [];
$previewText = (string) ($previewText ?? '');
$isTextPreview = (bool) ($isTextPreview ?? false);

ob_start();
?>
<section class="card mb-3">
    <div class="card-body">
        <h3 class="h6 mb-3"><?= htmlspecialchars($backupName, ENT_QUOTES, 'UTF-8') ?></h3>
        <p class="small text-body-secondary mb-1"><?= htmlspecialchars(__('maintenance.read.path'), ENT_QUOTES, 'UTF-8') ?>: <?= htmlspecialchars($backupPath, ENT_QUOTES, 'UTF-8') ?></p>
        <p class="small text-body-secondary mb-3"><?= htmlspecialchars(__('maintenance.read.size'), ENT_QUOTES, 'UTF-8') ?>: <?= number_format($backupSize / 1024, 1, '.', ' ') ?> KB</p>

        <div class="row g-3 mb-3">
            <div class="col-12 col-lg-6">
                <div class="border rounded p-2 bg-body-tertiary small">
                    <strong><?= htmlspecialchars(__('maintenance.read.exact_type'), ENT_QUOTES, 'UTF-8') ?></strong>: <?= htmlspecialchars((string) ($backupDetails['backup_type'] ?? ($backupManifest['backup_type'] ?? '-')), ENT_QUOTES, 'UTF-8') ?><br>
                    <strong><?= htmlspecialchars(__('maintenance.read.core_version'), ENT_QUOTES, 'UTF-8') ?></strong>: <?= htmlspecialchars((string) ($backupDetails['core_version'] ?? ($backupManifest['core_version'] ?? '-')), ENT_QUOTES, 'UTF-8') ?><br>
                    <strong><?= htmlspecialchars(__('maintenance.read.backup_format'), ENT_QUOTES, 'UTF-8') ?></strong>: <?= htmlspecialchars((string) ($backupDetails['backup_format_version'] ?? ($backupManifest['backup_format_version'] ?? '-')), ENT_QUOTES, 'UTF-8') ?><br>
                    <strong><?= htmlspecialchars(__('maintenance.read.checksum'), ENT_QUOTES, 'UTF-8') ?></strong>: <?= htmlspecialchars((string) (($backupManifest['file']['checksum_sha256'] ?? '-')), ENT_QUOTES, 'UTF-8') ?>
                </div>
            </div>
            <div class="col-12 col-lg-6">
                <div class="border rounded p-2 bg-body-tertiary small">
                    <?php $content = (array) ($backupDetails['content'] ?? []); ?>
                    <strong><?= htmlspecialchars(__('maintenance.read.content_detected'), ENT_QUOTES, 'UTF-8') ?></strong><br>
                    SQL: <?= !empty($content['sql_full']) ? htmlspecialchars(__('maintenance.read.yes'), ENT_QUOTES, 'UTF-8') : htmlspecialchars(__('maintenance.read.no'), ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars(__('maintenance.read.tables'), ENT_QUOTES, 'UTF-8') ?>: <?= (int) ($content['sql_tables_count'] ?? 0) ?>)<br>
                    <?= htmlspecialchars(__('maintenance.read.files'), ENT_QUOTES, 'UTF-8') ?>: <?= !empty($content['files']) ? htmlspecialchars(__('maintenance.read.yes'), ENT_QUOTES, 'UTF-8') : htmlspecialchars(__('maintenance.read.no'), ENT_QUOTES, 'UTF-8') ?> / <?= htmlspecialchars(__('maintenance.read.uploads'), ENT_QUOTES, 'UTF-8') ?>: <?= !empty($content['uploads']) ? htmlspecialchars(__('maintenance.read.yes'), ENT_QUOTES, 'UTF-8') : htmlspecialchars(__('maintenance.read.no'), ENT_QUOTES, 'UTF-8') ?><br>
                    <?= htmlspecialchars(__('maintenance.read.config'), ENT_QUOTES, 'UTF-8') ?>: <?= !empty($content['config']) ? htmlspecialchars(__('maintenance.read.yes'), ENT_QUOTES, 'UTF-8') : htmlspecialchars(__('maintenance.read.no'), ENT_QUOTES, 'UTF-8') ?> / <?= htmlspecialchars(__('maintenance.read.assets'), ENT_QUOTES, 'UTF-8') ?>: <?= !empty($content['assets']) ? htmlspecialchars(__('maintenance.read.yes'), ENT_QUOTES, 'UTF-8') : htmlspecialchars(__('maintenance.read.no'), ENT_QUOTES, 'UTF-8') ?> / <?= htmlspecialchars(__('maintenance.read.modules'), ENT_QUOTES, 'UTF-8') ?>: <?= !empty($content['modules']) ? htmlspecialchars(__('maintenance.read.yes'), ENT_QUOTES, 'UTF-8') : htmlspecialchars(__('maintenance.read.no'), ENT_QUOTES, 'UTF-8') ?>
                </div>
            </div>
        </div>

        <?php $warnings = (array) ($backupDetails['warnings'] ?? []); ?>
        <?php if ($warnings !== []): ?>
            <div class="alert alert-warning">
                <strong><?= htmlspecialchars(__('maintenance.read.compat_warnings'), ENT_QUOTES, 'UTF-8') ?></strong>
                <ul class="mb-0 mt-2">
                    <?php foreach ($warnings as $warning): ?>
                        <li><?= htmlspecialchars((string) $warning, ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="alert alert-info">
            <strong><?= htmlspecialchars(__('maintenance.read.restore_strategies_title'), ENT_QUOTES, 'UTF-8') ?></strong><br>
            <?= htmlspecialchars(__('maintenance.read.restore_strategies_body'), ENT_QUOTES, 'UTF-8') ?>
        </div>

        <?php if ($isTextPreview): ?>
            <pre class="small border rounded p-3 bg-body-tertiary mb-0 cat-scroll-block-lg"><?= htmlspecialchars($previewText, ENT_QUOTES, 'UTF-8') ?></pre>
        <?php else: ?>
            <div class="alert alert-info mb-0"><?= htmlspecialchars(__('maintenance.read.no_text_preview'), ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
    </div>
</section>

<a class="btn btn-outline-secondary btn-sm" href="<?= htmlspecialchars($adminBase . '/maintenance', ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(__('maintenance.read.back_to_maintenance'), ENT_QUOTES, 'UTF-8') ?></a>
<?php
$content = (string) ob_get_clean();
require CATMIN_ADMIN . '/views/layouts/admin.php';
