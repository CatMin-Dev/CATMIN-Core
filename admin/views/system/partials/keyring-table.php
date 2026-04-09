<?php

declare(strict_types=1);

$groups = is_array($groups ?? null) ? $groups : [];
$scopeLabels = [
    'official' => __('trust.scope.official'),
    'trusted' => __('trust.scope.trusted'),
    'community' => __('trust.scope.community'),
    'local_only' => __('trust.scope.local_only'),
    'revoked' => __('trust.scope.revoked'),
];
$scopeOrder = ['official', 'trusted', 'community', 'local_only', 'revoked'];
?>
<section class="card mb-3">
    <div class="card-header bg-transparent border-0 pt-3">
        <h3 class="h6 mb-0"><?= htmlspecialchars(__('trust.keys.title'), ENT_QUOTES, 'UTF-8') ?></h3>
    </div>
    <div class="card-body pt-2">
        <?php foreach ($scopeOrder as $scope): ?>
            <?php $rows = is_array($groups[$scope] ?? null) ? $groups[$scope] : []; ?>
            <div class="mb-3">
                <h4 class="h6 mb-2"><?= htmlspecialchars((string) ($scopeLabels[$scope] ?? strtoupper($scope)), ENT_QUOTES, 'UTF-8') ?> <span class="text-body-secondary">(<?= count($rows) ?>)</span></h4>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th><?= htmlspecialchars(__('trust.keys.key_id'), ENT_QUOTES, 'UTF-8') ?></th>
                                <th><?= htmlspecialchars(__('trust.keys.publisher'), ENT_QUOTES, 'UTF-8') ?></th>
                                <th><?= htmlspecialchars(__('trust.keys.source'), ENT_QUOTES, 'UTF-8') ?></th>
                                <th><?= htmlspecialchars(__('trust.keys.fingerprint'), ENT_QUOTES, 'UTF-8') ?></th>
                                <th><?= htmlspecialchars(__('common.actions'), ENT_QUOTES, 'UTF-8') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($rows === []): ?>
                            <tr><td colspan="5" class="text-body-secondary small"><?= htmlspecialchars(__('trust.keys.empty_scope'), ENT_QUOTES, 'UTF-8') ?></td></tr>
                        <?php else: ?>
                            <?php foreach ($rows as $entry): ?>
                                <?php
                                $keyId = (string) ($entry['key_id'] ?? '');
                                $fingerprint = strtoupper(substr((string) ($entry['fingerprint'] ?? ''), 0, 16));
                                $editable = (bool) ($entry['editable'] ?? false);
                                ?>
                                <tr>
                                    <td><code><?= htmlspecialchars($keyId, ENT_QUOTES, 'UTF-8') ?></code></td>
                                    <td><?= htmlspecialchars((string) ($entry['publisher'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string) ($entry['source'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="small text-body-secondary"><code><?= htmlspecialchars($fingerprint !== '' ? $fingerprint : '-', ENT_QUOTES, 'UTF-8') ?></code></td>
                                    <td>
                                        <?php if ($editable): ?>
                                            <form method="post" action="<?= htmlspecialchars($adminBase . '/system/trust-center/local-keys/delete', ENT_QUOTES, 'UTF-8') ?>" class="d-inline">
                                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string) ($_SESSION['csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="key_id" value="<?= htmlspecialchars($keyId, ENT_QUOTES, 'UTF-8') ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger"><?= htmlspecialchars(__('common.delete'), ENT_QUOTES, 'UTF-8') ?></button>
                                            </form>
                                        <?php else: ?>
                                            <span class="badge text-bg-secondary"><?= htmlspecialchars(__('trust.keys.protected'), ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
