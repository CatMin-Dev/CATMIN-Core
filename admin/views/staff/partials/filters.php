<?php

declare(strict_types=1);

$filters = isset($filters) && is_array($filters) ? $filters : [];
$roleOptions = isset($roleOptions) && is_array($roleOptions) ? $roleOptions : [];
$pagination = isset($pagination) && is_array($pagination) ? $pagination : ['total' => 0];
?>
<form method="get" class="card" data-staff-filters>
    <div class="card-body py-3">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-xl-4">
                <label class="form-label mb-1" for="staff-q">Recherche</label>
                <input id="staff-q" type="search" class="form-control" name="q" value="<?= htmlspecialchars((string) ($filters['q'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Username / email">
            </div>
            <div class="col-6 col-xl-2">
                <label class="form-label mb-1" for="staff-role">Role</label>
                <select id="staff-role" class="form-select" name="role">
                    <option value="">Tous</option>
                    <?php foreach ($roleOptions as $role): ?>
                        <?php $slug = (string) ($role['slug'] ?? ''); ?>
                        <option value="<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>" <?= ($slug === (string) ($filters['role'] ?? '')) ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string) ($role['name'] ?? $slug), ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-xl-2">
                <label class="form-label mb-1" for="staff-status">Statut</label>
                <select id="staff-status" class="form-select" name="status">
                    <option value="">Tous</option>
                    <option value="active" <?= ((string) ($filters['status'] ?? '') === 'active') ? 'selected' : '' ?>>Actif</option>
                    <option value="inactive" <?= ((string) ($filters['status'] ?? '') === 'inactive') ? 'selected' : '' ?>>Inactif</option>
                </select>
            </div>
            <div class="col-6 col-xl-2">
                <label class="form-label mb-1" for="staff-sort">Tri</label>
                <select id="staff-sort" class="form-select" name="sort">
                    <?php
                    $sortOptions = [
                        'created_at' => 'Creation',
                        'last_login_at' => 'Derniere connexion',
                        'username' => 'Username',
                        'email' => 'Email',
                        'role' => 'Role',
                    ];
                    foreach ($sortOptions as $key => $label):
                    ?>
                        <option value="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" <?= ((string) ($filters['sort'] ?? '') === $key) ? 'selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-xl-2 d-flex gap-2">
                <input type="hidden" name="dir" value="<?= htmlspecialchars((string) ($filters['dir'] ?? 'desc'), ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" class="btn btn-primary flex-grow-1">Filtrer</button>
                <a href="<?= htmlspecialchars((string) ($adminBase ?? '/admin') . '/staff', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary" data-staff-reset>Reset</a>
            </div>
        </div>
        <p class="small text-body-secondary mb-0 mt-2">Resultats: <strong><?= (int) ($pagination['total'] ?? 0) ?></strong></p>
    </div>
</form>
