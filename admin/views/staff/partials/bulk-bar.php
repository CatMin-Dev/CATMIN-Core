<?php

declare(strict_types=1);

use Core\security\CsrfManager;
?>
<form method="post" action="<?= htmlspecialchars((string) ($adminBase ?? '/admin') . '/staff/bulk', ENT_QUOTES, 'UTF-8') ?>" class="card d-none" data-bulk-bar>
    <div class="card-body py-2 d-flex flex-wrap align-items-center gap-2">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars((new CsrfManager())->token(), ENT_QUOTES, 'UTF-8') ?>">
        <span class="small text-body-secondary" data-bulk-count>0 selection</span>
        <select class="form-select form-select-sm w-auto" name="action" required>
            <option value="">Action</option>
            <option value="enable">Activer</option>
            <option value="disable">Desactiver</option>
            <option value="role">Changer role</option>
        </select>
        <select class="form-select form-select-sm w-auto" name="role_id" data-bulk-role>
            <option value="">Role cible</option>
            <?php foreach ($roleOptions as $role): ?>
                <option value="<?= (int) ($role['id'] ?? 0) ?>"><?= htmlspecialchars((string) ($role['name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-sm btn-outline-primary">Executer</button>
    </div>
</form>
