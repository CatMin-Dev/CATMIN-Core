<?php

declare(strict_types=1);

use Core\security\CsrfManager;

$pageTitle = 'Queue Engine';
$pageDescription = '';
$activeNav = 'queue';
$breadcrumbs = [
    ['label' => __('common.admin'), 'href' => $adminBase . '/'],
    ['label' => __('nav.system')],
    ['label' => 'Queue'],
];

$rows = is_array($rows ?? null) ? $rows : [];
$stats = is_array($stats ?? null) ? $stats : ['pending' => 0, 'running' => 0, 'retry' => 0, 'failed' => 0, 'done' => 0];
$csrf = htmlspecialchars((new CsrfManager())->token(), ENT_QUOTES, 'UTF-8');

ob_start();
?>
<section class="row g-3 mb-3">
    <div class="col-6 col-xl-2"><div class="card h-100"><div class="card-body"><small class="text-body-secondary d-block">Pending</small><strong><?= (int) ($stats['pending'] ?? 0) ?></strong></div></div></div>
    <div class="col-6 col-xl-2"><div class="card h-100"><div class="card-body"><small class="text-body-secondary d-block">Running</small><strong><?= (int) ($stats['running'] ?? 0) ?></strong></div></div></div>
    <div class="col-6 col-xl-2"><div class="card h-100"><div class="card-body"><small class="text-body-secondary d-block">Retry</small><strong><?= (int) ($stats['retry'] ?? 0) ?></strong></div></div></div>
    <div class="col-6 col-xl-2"><div class="card h-100"><div class="card-body"><small class="text-body-secondary d-block">Failed</small><strong class="text-danger"><?= (int) ($stats['failed'] ?? 0) ?></strong></div></div></div>
    <div class="col-6 col-xl-2"><div class="card h-100"><div class="card-body"><small class="text-body-secondary d-block">Done</small><strong class="text-success"><?= (int) ($stats['done'] ?? 0) ?></strong></div></div></div>
    <div class="col-6 col-xl-2">
        <form method="post" action="<?= htmlspecialchars($adminBase . '/system/queue/enqueue-test', ENT_QUOTES, 'UTF-8') ?>" class="card h-100">
            <div class="card-body d-grid">
                <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                <button type="submit" class="btn btn-primary btn-sm">Ajouter job test</button>
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
                <th>Queue</th>
                <th>Type</th>
                <th>Status</th>
                <th>Attempts</th>
                <th>Created</th>
                <th>Last error</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($rows === []): ?>
                <tr><td colspan="7" class="text-center py-4 text-body-secondary">Aucun job.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $row): ?>
                    <?php $status = strtolower((string) ($row['status'] ?? 'pending')); ?>
                    <tr>
                        <td><?= (int) ($row['id'] ?? 0) ?></td>
                        <td><?= htmlspecialchars((string) ($row['queue'] ?? 'default'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($row['job_type'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <span class="badge <?= match ($status) {
                                'done' => 'text-bg-success',
                                'failed' => 'text-bg-danger',
                                'running' => 'text-bg-info',
                                'retry' => 'text-bg-warning',
                                default => 'text-bg-secondary',
                            } ?>"><?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?></span>
                        </td>
                        <td><?= (int) ($row['attempts'] ?? 0) ?>/<?= (int) ($row['max_attempts'] ?? 0) ?></td>
                        <td><?= htmlspecialchars((string) ($row['created_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="small text-body-secondary"><?= htmlspecialchars((string) ($row['last_error'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
$content = (string) ob_get_clean();
require CATMIN_ADMIN . '/views/layouts/admin.php';

