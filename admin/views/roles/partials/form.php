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
            <div class="alert alert-warning py-2">Role systeme critique: edition restreinte.</div>
        <?php endif; ?>
        <div class="row g-3">
            <div class="col-12 col-lg-6">
                <label class="form-label">Nom</label>
                <input type="text" name="name" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars((string) ($values['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" <?= $isCritical ? 'readonly' : '' ?> required>
                <?php if (isset($errors['name'])): ?><div class="invalid-feedback"><?= htmlspecialchars((string) $errors['name'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
            </div>
            <div class="col-12 col-lg-6">
                <label class="form-label">Slug</label>
                <input type="text" name="slug" class="form-control <?= isset($errors['slug']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars((string) ($values['slug'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" <?= $isCritical ? 'readonly' : '' ?> required>
                <?php if (isset($errors['slug'])): ?><div class="invalid-feedback"><?= htmlspecialchars((string) $errors['slug'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
            </div>
            <div class="col-12">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="2" <?= $isCritical ? 'readonly' : '' ?>><?= htmlspecialchars((string) ($values['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
            <div class="col-12 col-lg-4">
                <label class="form-label">Niveau</label>
                <select name="level" class="form-select" <?= $isCritical ? 'disabled' : '' ?>>
                    <option value="standard" <?= ((string) ($values['level'] ?? '') === 'standard') ? 'selected' : '' ?>>Standard</option>
                    <option value="critical" <?= ((string) ($values['level'] ?? '') === 'critical') ? 'selected' : '' ?>>Critique</option>
                </select>
                <?php if ($isCritical): ?><input type="hidden" name="level" value="critical"><?php endif; ?>
            </div>
        </div>
    </div>
</div>
