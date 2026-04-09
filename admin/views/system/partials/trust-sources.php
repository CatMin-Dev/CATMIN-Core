<?php

declare(strict_types=1);

$sources = is_array($sources ?? null) ? $sources : [];
?>
<section class="card mb-3">
    <div class="card-header bg-transparent border-0 pt-3">
        <h3 class="h6 mb-0"><?= htmlspecialchars(__('trust.sources.title'), ENT_QUOTES, 'UTF-8') ?></h3>
    </div>
    <div class="card-body pt-2">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th><?= htmlspecialchars(__('trust.sources.source'), ENT_QUOTES, 'UTF-8') ?></th>
                        <th><?= htmlspecialchars(__('common.status'), ENT_QUOTES, 'UTF-8') ?></th>
                        <th><?= htmlspecialchars(__('trust.sources.details'), ENT_QUOTES, 'UTF-8') ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($sources as $source): ?>
                    <?php
                    $status = strtolower(trim((string) ($source['status'] ?? 'unknown')));
                    $badge = match ($status) {
                        'ok' => 'text-bg-success',
                        'warning' => 'text-bg-warning',
                        'error' => 'text-bg-danger',
                        'disabled' => 'text-bg-secondary',
                        default => 'text-bg-dark',
                    };
                    ?>
                    <tr>
                        <td><?= htmlspecialchars((string) ($source['label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><span class="badge <?= $badge ?>"><?= htmlspecialchars(strtoupper($status), ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td class="text-body-secondary small"><?= htmlspecialchars((string) ($source['details'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
