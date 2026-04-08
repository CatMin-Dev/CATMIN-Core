<?php

declare(strict_types=1);

$values = isset($values) && is_array($values) ? $values : [];
$errors = isset($errors) && is_array($errors) ? $errors : [];
$roles = isset($roles) && is_array($roles) ? $roles : [];
$isEdit = isset($isEdit) ? (bool) $isEdit : false;
$isSuperAdmin = isset($isSuperAdmin) ? (bool) $isSuperAdmin : false;
?>
<div class="card">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-12 col-lg-6">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control <?= isset($errors['username']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars((string) ($values['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                <?php if (isset($errors['username'])): ?><div class="invalid-feedback"><?= htmlspecialchars((string) $errors['username'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
            </div>
            <div class="col-12 col-lg-6">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars((string) ($values['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                <?php if (isset($errors['email'])): ?><div class="invalid-feedback"><?= htmlspecialchars((string) $errors['email'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
            </div>

            <div class="col-12 col-lg-6">
                <label class="form-label">Role</label>
                <select name="role_id" class="form-select <?= isset($errors['role_id']) ? 'is-invalid' : '' ?>" <?= $isSuperAdmin ? 'disabled' : '' ?>>
                    <option value="">Selectionner</option>
                    <?php foreach ($roles as $role): ?>
                        <?php $id = (int) ($role['id'] ?? 0); ?>
                        <option value="<?= $id ?>" <?= ((string) ($values['role_id'] ?? '') === (string) $id) ? 'selected' : '' ?>><?= htmlspecialchars((string) ($role['name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if ($isSuperAdmin): ?><input type="hidden" name="role_id" value="<?= htmlspecialchars((string) ($values['role_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"><?php endif; ?>
                <?php if (isset($errors['role_id'])): ?><div class="invalid-feedback"><?= htmlspecialchars((string) $errors['role_id'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
            </div>
            <div class="col-12 col-lg-6">
                <label class="form-label">Statut</label>
                <select name="is_active" class="form-select" <?= $isSuperAdmin ? 'disabled' : '' ?>>
                    <option value="1" <?= ((string) ($values['is_active'] ?? '1') === '1') ? 'selected' : '' ?>>Actif</option>
                    <option value="0" <?= ((string) ($values['is_active'] ?? '1') === '0') ? 'selected' : '' ?>>Inactif</option>
                </select>
                <?php if ($isSuperAdmin): ?><input type="hidden" name="is_active" value="1"><?php endif; ?>
            </div>

            <?php if (!$isEdit): ?>
                <div class="col-12 col-lg-6">
                    <label class="form-label">Mot de passe</label>
                    <input type="password" name="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" minlength="12" required data-staff-password>
                    <div class="form-text">Minimum 12 caracteres.</div>
                    <?php if (isset($errors['password'])): ?><div class="invalid-feedback"><?= htmlspecialchars((string) $errors['password'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                </div>
                <div class="col-12 col-lg-6">
                    <label class="form-label">Confirmation mot de passe</label>
                    <input type="password" name="password_confirm" class="form-control <?= isset($errors['password_confirm']) ? 'is-invalid' : '' ?>" minlength="12" required>
                    <?php if (isset($errors['password_confirm'])): ?><div class="invalid-feedback"><?= htmlspecialchars((string) $errors['password_confirm'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
