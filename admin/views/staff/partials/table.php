<?php

declare(strict_types=1);

use Core\security\CsrfManager;

$rows = isset($rows) && is_array($rows) ? $rows : [];
$csrfToken = htmlspecialchars((new CsrfManager())->token(), ENT_QUOTES, 'UTF-8');
?>
<section class="card">
    <div class="table-responsive cat-staff-table-wrap">
        <?php if ($rows === []): ?>
            <div class="card-body py-4">
                <?php require __DIR__ . '/empty-state.php'; ?>
            </div>
        <?php else: ?>
            <table class="table table-hover align-middle mb-0 cat-staff-table">
                <thead>
                <tr>
                    <th class="text-center cat-staff-col-select"><input type="checkbox" class="form-check-input" data-bulk-master></th>
                    <th><?= htmlspecialchars(__('staff.table.account'), ENT_QUOTES, 'UTF-8') ?></th>
                    <th><?= htmlspecialchars(__('common.role'), ENT_QUOTES, 'UTF-8') ?></th>
                    <th><?= htmlspecialchars(__('common.status'), ENT_QUOTES, 'UTF-8') ?></th>
                    <th>SuperAdmin</th>
                    <th><?= htmlspecialchars(__('common.last_login'), ENT_QUOTES, 'UTF-8') ?></th>
                    <th><?= htmlspecialchars(__('common.creation'), ENT_QUOTES, 'UTF-8') ?></th>
                    <th class="text-end"><?= htmlspecialchars(__('common.actions'), ENT_QUOTES, 'UTF-8') ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <?php
                    $staffId = (int) ($row['id'] ?? 0);
                    $isSuperAdmin = ((string) ($row['role_slug'] ?? '') === 'super-admin');
                    $isActive = ((int) ($row['is_active'] ?? 0)) === 1;
                    $enableFormId = 'cat-staff-enable-' . $staffId;
                    $disableFormId = 'cat-staff-disable-' . $staffId;
                    $actionSelectId = 'cat-staff-action-select-' . $staffId;
                    ?>
                    <tr>
                        <td class="text-center">
                            <input type="checkbox" class="form-check-input" data-bulk-item value="<?= (int) ($row['id'] ?? 0) ?>" <?= $isSuperAdmin ? 'disabled' : '' ?>>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="cat-avatar-dot"><?= htmlspecialchars(strtoupper(substr((string) ($row['username'] ?? '?'), 0, 1)), ENT_QUOTES, 'UTF-8') ?></span>
                                <div>
                                    <p class="mb-0 fw-semibold"><?= htmlspecialchars((string) ($row['username'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                                    <small class="text-body-secondary"><?= htmlspecialchars((string) ($row['email'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></small>
                                </div>
                            </div>
                        </td>
                        <td><span class="badge text-bg-info"><?= htmlspecialchars((string) ($row['role_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td>
                            <span class="badge <?= $isActive ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= htmlspecialchars($isActive ? __('common.active') : __('common.inactive'), ENT_QUOTES, 'UTF-8') ?></span>
                        </td>
                        <td>
                            <?php if ($isSuperAdmin): ?>
                                <span class="badge text-bg-danger"><?= htmlspecialchars(__('common.yes'), ENT_QUOTES, 'UTF-8') ?></span>
                            <?php else: ?>
                                <span class="badge text-bg-light border"><?= htmlspecialchars(__('common.no'), ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars((string) ($row['last_login_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($row['created_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="text-end cat-staff-actions-cell">
                            <?php if (!$isSuperAdmin): ?>
                                <div class="d-none" aria-hidden="true">
                                    <form id="<?= htmlspecialchars($enableFormId, ENT_QUOTES, 'UTF-8') ?>" method="post" action="<?= htmlspecialchars((string) ($adminBase ?? '/admin') . '/staff/' . $staffId . '/enable', ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="_csrf" value="<?= $csrfToken ?>">
                                        <button type="submit" data-cat-submitter><?= htmlspecialchars(__('common.enable'), ENT_QUOTES, 'UTF-8') ?></button>
                                    </form>
                                    <form id="<?= htmlspecialchars($disableFormId, ENT_QUOTES, 'UTF-8') ?>" method="post" action="<?= htmlspecialchars((string) ($adminBase ?? '/admin') . '/staff/' . $staffId . '/disable', ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="_csrf" value="<?= $csrfToken ?>">
                                        <button type="submit" data-cat-submitter><?= htmlspecialchars(__('common.disable'), ENT_QUOTES, 'UTF-8') ?></button>
                                    </form>
                                </div>
                            <?php endif; ?>
                            <div class="input-group input-group-sm cat-row-actions-group cat-staff-row-actions-group">
                                <a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars((string) ($adminBase ?? '/admin') . '/staff/' . $staffId, ENT_QUOTES, 'UTF-8') ?>" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="<?= htmlspecialchars(__('common.view'), ENT_QUOTES, 'UTF-8') ?>" aria-label="<?= htmlspecialchars(__('common.view'), ENT_QUOTES, 'UTF-8') ?>">
                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                    <span class="visually-hidden"><?= htmlspecialchars(__('common.view'), ENT_QUOTES, 'UTF-8') ?></span>
                                </a>
                                <a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars((string) ($adminBase ?? '/admin') . '/staff/' . $staffId . '/edit', ENT_QUOTES, 'UTF-8') ?>" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="<?= htmlspecialchars(__('common.edit'), ENT_QUOTES, 'UTF-8') ?>" aria-label="<?= htmlspecialchars(__('common.edit'), ENT_QUOTES, 'UTF-8') ?>">
                                    <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                    <span class="visually-hidden"><?= htmlspecialchars(__('common.edit'), ENT_QUOTES, 'UTF-8') ?></span>
                                </a>
                                <?php if (!$isSuperAdmin): ?>
                                    <select id="<?= htmlspecialchars($actionSelectId, ENT_QUOTES, 'UTF-8') ?>" class="form-select cat-row-action-select" aria-label="<?= htmlspecialchars(__('common.actions'), ENT_QUOTES, 'UTF-8') ?>">
                                        <option value="<?= htmlspecialchars($enableFormId, ENT_QUOTES, 'UTF-8') ?>" <?= $isActive ? '' : 'selected' ?>><?= htmlspecialchars(__('common.enable'), ENT_QUOTES, 'UTF-8') ?></option>
                                        <option value="<?= htmlspecialchars($disableFormId, ENT_QUOTES, 'UTF-8') ?>" <?= $isActive ? 'selected' : '' ?>><?= htmlspecialchars(__('common.disable'), ENT_QUOTES, 'UTF-8') ?></option>
                                    </select>
                                    <button class="btn btn-sm btn-outline-success" type="button" data-cat-submit-form-from-select="<?= htmlspecialchars($actionSelectId, ENT_QUOTES, 'UTF-8') ?>" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="<?= htmlspecialchars(__('common.apply'), ENT_QUOTES, 'UTF-8') ?>" aria-label="<?= htmlspecialchars(__('common.apply'), ENT_QUOTES, 'UTF-8') ?>">
                                        <i class="bi bi-check2-circle" aria-hidden="true"></i>
                                        <span class="visually-hidden"><?= htmlspecialchars(__('common.apply'), ENT_QUOTES, 'UTF-8') ?></span>
                                    </button>
                                <?php else: ?>
                                    <span class="input-group-text text-body-secondary"><?= htmlspecialchars(__('staff.superadmin.protected_short'), ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</section>
