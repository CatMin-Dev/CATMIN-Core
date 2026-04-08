<?php

declare(strict_types=1);

$pageTitle = 'Lecture backup';
$pageDescription = '';
$activeNav = 'maintenance';
$breadcrumbs = [
    ['label' => 'Admin', 'href' => $adminBase . '/'],
    ['label' => 'Backup / Maintenance', 'href' => $adminBase . '/maintenance'],
    ['label' => (string) ($backupName ?? 'Backup')],
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
        <p class="small text-body-secondary mb-1">Chemin: <?= htmlspecialchars($backupPath, ENT_QUOTES, 'UTF-8') ?></p>
        <p class="small text-body-secondary mb-3">Taille: <?= number_format($backupSize / 1024, 1, '.', ' ') ?> KB</p>
        <?php if ($isTextPreview): ?>
            <pre class="small border rounded p-3 bg-body-tertiary mb-0" style="max-height: 60vh; overflow: auto;"><?= htmlspecialchars($previewText, ENT_QUOTES, 'UTF-8') ?></pre>
        <?php else: ?>
            <div class="alert alert-info mb-0">Aperçu texte non disponible pour ce format.</div>
        <?php endif; ?>
    </div>
</section>

<a class="btn btn-outline-secondary btn-sm" href="<?= htmlspecialchars($adminBase . '/maintenance', ENT_QUOTES, 'UTF-8') ?>">Retour maintenance</a>
<?php
$content = (string) ob_get_clean();
require CATMIN_ADMIN . '/views/layouts/admin.php';
