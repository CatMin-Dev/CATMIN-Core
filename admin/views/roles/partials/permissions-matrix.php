<?php

declare(strict_types=1);

$permissionMatrix = isset($permissionMatrix) && is_array($permissionMatrix) ? $permissionMatrix : [];
$selectedPermissions = isset($selectedPermissions) && is_array($selectedPermissions) ? array_map('intval', $selectedPermissions) : [];
?>
<section class="card">
    <div class="card-header bg-transparent border-0 pt-3 d-flex justify-content-between align-items-center">
        <h3 class="h6 mb-0"><?= htmlspecialchars(__('roles.matrix.title'), ENT_QUOTES, 'UTF-8') ?></h3>
        <label class="form-check form-switch m-0">
            <input class="form-check-input" type="checkbox" data-matrix-all>
            <span class="form-check-label small"><?= htmlspecialchars(__('roles.matrix.select_all'), ENT_QUOTES, 'UTF-8') ?></span>
        </label>
    </div>
    <div class="card-body pt-2">
        <?php if ($permissionMatrix === []): ?>
            <p class="small text-body-secondary mb-0"><?= htmlspecialchars(__('roles.matrix.empty'), ENT_QUOTES, 'UTF-8') ?></p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm align-middle cat-permissions-matrix mb-0">
                    <thead>
                    <tr>
                        <th><?= htmlspecialchars(__('common.module'), ENT_QUOTES, 'UTF-8') ?></th>
                        <th><?= htmlspecialchars(__('common.actions'), ENT_QUOTES, 'UTF-8') ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($permissionMatrix as $group): ?>
                        <?php $moduleName = (string) ($group['module'] ?? 'core'); ?>
                        <tr>
                            <td class="fw-semibold">
                                <label class="form-check d-inline-flex align-items-center gap-2 m-0">
                                    <input
                                        class="form-check-input m-0"
                                        type="checkbox"
                                        data-matrix-row
                                        value="<?= htmlspecialchars($moduleName, ENT_QUOTES, 'UTF-8') ?>"
                                    >
                                    <span><?= htmlspecialchars($moduleName, ENT_QUOTES, 'UTF-8') ?></span>
                                </label>
                            </td>
                            <td>
                                <div class="cat-permissions-actions">
                                    <?php foreach ((array) ($group['permissions'] ?? []) as $permission): ?>
                                        <?php $pid = (int) ($permission['id'] ?? 0); ?>
                                        <label class="form-check m-0 px-2 py-1 rounded border d-flex align-items-center gap-2">
                                            <input class="form-check-input m-0" type="checkbox" name="permissions[]" value="<?= $pid ?>" data-matrix-cell data-module="<?= htmlspecialchars($moduleName, ENT_QUOTES, 'UTF-8') ?>" <?= in_array($pid, $selectedPermissions, true) ? 'checked' : '' ?>>
                                            <span class="small"><?= htmlspecialchars((string) ($permission['action'] ?? $permission['slug'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>
