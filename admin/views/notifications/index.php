<?php

declare(strict_types=1);

$rows = isset($rows) && is_array($rows) ? $rows : [];

$pageTitle = 'Notifications';
$pageDescription = '';
$activeNav = 'system';
$breadcrumbs = [
    ['label' => 'Admin', 'href' => $adminBase . '/'],
    ['label' => 'Notifications'],
];

ob_start();
?>
<section class="card">
    <div class="card-header bg-transparent border-0 pt-3 d-flex justify-content-between align-items-center">
        <h3 class="h6 mb-0">Notifications internes</h3>
        <a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars($adminBase . '/notifications/mark-all-read?next=' . rawurlencode($adminBase . '/notifications'), ENT_QUOTES, 'UTF-8') ?>">Tout marquer comme lu</a>
    </div>
    <div class="card-body pt-2">
        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Titre</th>
                        <th>Message</th>
                        <th>Type</th>
                        <th>Source</th>
                        <th>Date</th>
                        <th>Etat</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($rows === []): ?>
                        <tr><td colspan="7" class="text-body-secondary">Aucune notification.</td></tr>
                    <?php else: ?>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td><?= (int) ($row['id'] ?? 0) ?></td>
                                <td><?= htmlspecialchars((string) ($row['title'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) ($row['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><span class="badge text-bg-secondary"><?= htmlspecialchars(strtoupper((string) ($row['type'] ?? 'info')), ENT_QUOTES, 'UTF-8') ?></span></td>
                                <td><?= htmlspecialchars((string) ($row['source'] ?? 'core'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) ($row['created_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= !empty($row['is_read']) ? 'Lu' : 'Non lu' ?></td>
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
