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
$previewText = (string) ($previewText ?? '');
$isTextPreview = (bool) ($isTextPreview ?? false);

ob_start();
?>
<section class="card mb-3">
    <div class="card-body">
        <h3 class="h6 mb-3"><?= htmlspecialchars($backupName, ENT_QUOTES, 'UTF-8') ?></h3>
        <p class="small text-body-secondary mb-1"><?= htmlspecialchars(__('maintenance.read.path'), ENT_QUOTES, 'UTF-8') ?>: <?= htmlspecialchars($backupPath, ENT_QUOTES, 'UTF-8') ?></p>
        <p class="small text-body-secondary mb-3"><?= htmlspecialchars(__('maintenance.read.size'), ENT_QUOTES, 'UTF-8') ?>: <?= number_format($backupSize / 1024, 1, '.', ' ') ?> KB</p>
        <?php if ($isTextPreview): ?>
            <pre class="small border rounded p-3 bg-body-tertiary mb-0" style="max-height: 60vh; overflow: auto;"><?= htmlspecialchars($previewText, ENT_QUOTES, 'UTF-8') ?></pre>
        <?php else: ?>
            <div class="alert alert-info mb-0"><?= htmlspecialchars(__('maintenance.read.no_text_preview'), ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
    </div>
</section>

<a class="btn btn-outline-secondary btn-sm" href="<?= htmlspecialchars($adminBase . '/maintenance', ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(__('maintenance.read.back_to_maintenance'), ENT_QUOTES, 'UTF-8') ?></a>
<?php
$content = (string) ob_get_clean();
require CATMIN_ADMIN . '/views/layouts/admin.php';
