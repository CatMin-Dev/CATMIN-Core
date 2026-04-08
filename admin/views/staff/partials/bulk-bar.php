<?php

declare(strict_types=1);

use Core\security\CsrfManager;
?>
<form method="post" action="<?= htmlspecialchars((string) ($adminBase ?? '/admin') . '/staff/bulk', ENT_QUOTES, 'UTF-8') ?>" class="card d-none" data-bulk-bar>
    <div class="card-body py-2 d-flex flex-wrap align-items-center gap-2">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars((new CsrfManager())->token(), ENT_QUOTES, 'UTF-8') ?>">
        <span class="small text-body-secondary" data-bulk-count>0 <?= htmlspecialchars(__('staff.bulk.selection'), ENT_QUOTES, 'UTF-8') ?></span>
        <select class="form-select form-select-sm w-auto" name="action" required>
            <option value=""><?= htmlspecialchars(__('staff.bulk.action'), ENT_QUOTES, 'UTF-8') ?></option>
            <option value="enable"><?= htmlspecialchars(__('common.enable'), ENT_QUOTES, 'UTF-8') ?></option>
            <option value="disable"><?= htmlspecialchars(__('common.disable'), ENT_QUOTES, 'UTF-8') ?></option>
            <option value="role"><?= htmlspecialchars(__('staff.bulk.change_role'), ENT_QUOTES, 'UTF-8') ?></option>
        </select>
        <select class="form-select form-select-sm w-auto" name="role_id" data-bulk-role>
            <option value=""><?= htmlspecialchars(__('staff.bulk.target_role'), ENT_QUOTES, 'UTF-8') ?></option>
            <?php foreach ($roleOptions as $role): ?>
                <option value="<?= (int) ($role['id'] ?? 0) ?>"><?= htmlspecialchars((string) ($role['name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-sm btn-outline-primary"><?= htmlspecialchars(__('staff.bulk.execute'), ENT_QUOTES, 'UTF-8') ?></button>
    </div>
</form>
