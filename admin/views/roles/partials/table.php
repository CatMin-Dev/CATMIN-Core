<?php

declare(strict_types=1);
use Core\security\CsrfManager;
$rows = isset($rows) && is_array($rows) ? $rows : [];
$csrfToken = htmlspecialchars((new CsrfManager())->token(), ENT_QUOTES, 'UTF-8');
?>
<section class="card">
    <div class="table-responsive">
        <?php if ($rows === []): ?>
            <div class="card-body py-4">
                <?php
                $title = __('roles.empty.title');
                $description = __('roles.empty.description');
                require CATMIN_ADMIN . '/views/components/empty-states/basic.php';
                ?>
            </div>
        <?php else: ?>
            <table class="table table-hover align-middle mb-0">
                <thead>
                <tr>
                    <th><?= htmlspecialchars(__('common.role'), ENT_QUOTES, 'UTF-8') ?></th>
                    <th><?= htmlspecialchars(__('common.description'), ENT_QUOTES, 'UTF-8') ?></th>
                    <th><?= htmlspecialchars(__('roles.table.users'), ENT_QUOTES, 'UTF-8') ?></th>
                    <th><?= htmlspecialchars(__('roles.table.level'), ENT_QUOTES, 'UTF-8') ?></th>
                    <th class="text-end"><?= htmlspecialchars(__('common.actions'), ENT_QUOTES, 'UTF-8') ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <?php $critical = ((int) ($row['is_system'] ?? 0) === 1) || ((string) ($row['slug'] ?? '') === 'super-admin'); ?>
                    <?php $roleId = (int) ($row['id'] ?? 0); ?>
                    <?php $deleteFormId = 'cat-role-delete-' . $roleId; ?>
                    <tr>
                        <td>
                            <p class="mb-0 fw-semibold"><?= htmlspecialchars((string) ($row['name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                            <small class="text-body-secondary"><?= htmlspecialchars((string) ($row['slug'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></small>
                        </td>
                        <td><small class="text-body-secondary"><?= htmlspecialchars(__('roles.table.catmin_role'), ENT_QUOTES, 'UTF-8') ?></small></td>
                        <td><span class="badge text-bg-light border"><?= (int) ($row['users_count'] ?? 0) ?></span></td>
                        <td><?= $critical ? '<span class="badge text-bg-danger">' . htmlspecialchars(__('roles.level.critical'), ENT_QUOTES, 'UTF-8') . '</span>' : '<span class="badge text-bg-secondary">' . htmlspecialchars(__('roles.level.standard'), ENT_QUOTES, 'UTF-8') . '</span>' ?></td>
                        <td class="text-end">
                            <?php if (!$critical): ?>
                                <div class="d-none" aria-hidden="true">
                                    <form id="<?= htmlspecialchars($deleteFormId, ENT_QUOTES, 'UTF-8') ?>" method="post" action="<?= htmlspecialchars((string) ($adminBase ?? '/admin') . '/roles/' . $roleId . '/delete', ENT_QUOTES, 'UTF-8') ?>" data-cat-confirm="<?= htmlspecialchars(__('roles.delete_confirm'), ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="_csrf" value="<?= $csrfToken ?>">
                                        <button type="submit" data-cat-submitter><?= htmlspecialchars(__('common.delete'), ENT_QUOTES, 'UTF-8') ?></button>
                                    </form>
                                </div>
                            <?php endif; ?>
                            <div class="input-group input-group-sm cat-row-actions-group">
                                <a href="<?= htmlspecialchars((string) ($adminBase ?? '/admin') . '/roles/' . $roleId, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="<?= htmlspecialchars(__('common.view'), ENT_QUOTES, 'UTF-8') ?>" aria-label="<?= htmlspecialchars(__('common.view'), ENT_QUOTES, 'UTF-8') ?>">
                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                    <span class="visually-hidden"><?= htmlspecialchars(__('common.view'), ENT_QUOTES, 'UTF-8') ?></span>
                                </a>
                                <a href="<?= htmlspecialchars((string) ($adminBase ?? '/admin') . '/roles/' . $roleId . '/edit', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="<?= htmlspecialchars(__('common.edit'), ENT_QUOTES, 'UTF-8') ?>" aria-label="<?= htmlspecialchars(__('common.edit'), ENT_QUOTES, 'UTF-8') ?>">
                                    <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                    <span class="visually-hidden"><?= htmlspecialchars(__('common.edit'), ENT_QUOTES, 'UTF-8') ?></span>
                                </a>
                                <?php if (!$critical): ?>
                                    <button type="button" class="btn btn-sm btn-outline-danger" data-cat-submit-form="<?= htmlspecialchars($deleteFormId, ENT_QUOTES, 'UTF-8') ?>" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="<?= htmlspecialchars(__('common.delete'), ENT_QUOTES, 'UTF-8') ?>" aria-label="<?= htmlspecialchars(__('common.delete'), ENT_QUOTES, 'UTF-8') ?>">
                                        <i class="bi bi-trash" aria-hidden="true"></i>
                                        <span class="visually-hidden"><?= htmlspecialchars(__('common.delete'), ENT_QUOTES, 'UTF-8') ?></span>
                                    </button>
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
