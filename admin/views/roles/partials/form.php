<?php

declare(strict_types=1);

$values = isset($values) && is_array($values) ? $values : [];
$errors = isset($errors) && is_array($errors) ? $errors : [];
$role = isset($role) && is_array($role) ? $role : [];
$isCritical = ((int) ($role['is_system'] ?? 0) === 1) || ((string) ($role['slug'] ?? '') === 'super-admin');
?>
<div class="card">
    <div class="card-body">
        <?php if ($isCritical): ?>
            <div class="alert alert-warning py-2"><?= htmlspecialchars(__('roles.form.critical_notice'), ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <div class="row g-3">
            <div class="col-12 col-lg-6">
                <label class="form-label"><?= htmlspecialchars(__('common.name'), ENT_QUOTES, 'UTF-8') ?></label>
                <input type="text" id="role-name" name="name" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars((string) ($values['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" <?= $isCritical ? 'readonly' : '' ?> required>
                <?php if (isset($errors['name'])): ?><div class="invalid-feedback"><?= htmlspecialchars((string) $errors['name'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
            </div>
            <div class="col-12 col-lg-6">
                <label class="form-label"><?= htmlspecialchars(__('common.slug'), ENT_QUOTES, 'UTF-8') ?></label>
                <input type="text" id="role-slug" name="slug" data-cat-slug-target="#role-name" class="form-control <?= isset($errors['slug']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars((string) ($values['slug'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" <?= $isCritical ? 'readonly' : '' ?> required>
                <?php if (isset($errors['slug'])): ?><div class="invalid-feedback"><?= htmlspecialchars((string) $errors['slug'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
            </div>
            <div class="col-12">
                <label class="form-label"><?= htmlspecialchars(__('common.description'), ENT_QUOTES, 'UTF-8') ?></label>
                <textarea name="description" class="form-control" rows="2" <?= $isCritical ? 'readonly' : '' ?>><?= htmlspecialchars((string) ($values['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
            <div class="col-12 col-lg-4">
                <label class="form-label"><?= htmlspecialchars(__('roles.table.level'), ENT_QUOTES, 'UTF-8') ?></label>
                <select name="level" class="form-select" <?= $isCritical ? 'disabled' : '' ?>>
                    <option value="standard" <?= ((string) ($values['level'] ?? '') === 'standard') ? 'selected' : '' ?>><?= htmlspecialchars(__('roles.level.standard'), ENT_QUOTES, 'UTF-8') ?></option>
                    <option value="critical" <?= ((string) ($values['level'] ?? '') === 'critical') ? 'selected' : '' ?>><?= htmlspecialchars(__('roles.level.critical'), ENT_QUOTES, 'UTF-8') ?></option>
                </select>
                <?php if ($isCritical): ?><input type="hidden" name="level" value="critical"><?php endif; ?>
            </div>
        </div>
    </div>
</div>
