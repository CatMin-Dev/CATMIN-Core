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
                    <th class="text-center" style="width:40px;"><input type="checkbox" class="form-check-input" data-bulk-master></th>
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
                    $isSuperAdmin = ((string) ($row['role_slug'] ?? '') === 'super-admin');
                    $isActive = ((int) ($row['is_active'] ?? 0)) === 1;
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
                        <td class="text-end">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false"><?= htmlspecialchars(__('common.actions'), ENT_QUOTES, 'UTF-8') ?></button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="<?= htmlspecialchars((string) ($adminBase ?? '/admin') . '/staff/' . (int) ($row['id'] ?? 0), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(__('common.view'), ENT_QUOTES, 'UTF-8') ?></a></li>
                                    <li><a class="dropdown-item" href="<?= htmlspecialchars((string) ($adminBase ?? '/admin') . '/staff/' . (int) ($row['id'] ?? 0) . '/edit', ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(__('common.edit'), ENT_QUOTES, 'UTF-8') ?></a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <?php if (!$isSuperAdmin): ?>
                                        <?php if ($isActive): ?>
                                            <li>
                                                <form method="post" action="<?= htmlspecialchars((string) ($adminBase ?? '/admin') . '/staff/' . (int) ($row['id'] ?? 0) . '/disable', ENT_QUOTES, 'UTF-8') ?>">
                                                    <input type="hidden" name="_csrf" value="<?= $csrfToken ?>">
                                                    <button class="dropdown-item" type="submit"><?= htmlspecialchars(__('common.disable'), ENT_QUOTES, 'UTF-8') ?></button>
                                                </form>
                                            </li>
                                        <?php else: ?>
                                            <li>
                                                <form method="post" action="<?= htmlspecialchars((string) ($adminBase ?? '/admin') . '/staff/' . (int) ($row['id'] ?? 0) . '/enable', ENT_QUOTES, 'UTF-8') ?>">
                                                    <input type="hidden" name="_csrf" value="<?= $csrfToken ?>">
                                                    <button class="dropdown-item" type="submit"><?= htmlspecialchars(__('common.enable'), ENT_QUOTES, 'UTF-8') ?></button>
                                                </form>
                                            </li>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <li><span class="dropdown-item-text text-body-secondary small"><?= htmlspecialchars(__('staff.superadmin.protected_short'), ENT_QUOTES, 'UTF-8') ?></span></li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</section>
