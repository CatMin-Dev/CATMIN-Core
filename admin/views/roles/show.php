<?php

declare(strict_types=1);

$pageTitle = 'Detail role';
$pageDescription = 'Vue complete du role, droits actifs et utilisateurs associes.';
$activeNav = 'roles';
$breadcrumbs = [
    ['label' => 'Admin', 'href' => $adminBase . '/'],
    ['label' => 'Roles & Permissions', 'href' => $adminBase . '/roles'],
    ['label' => (string) ($role['name'] ?? 'Role')],
];
$pageActions = [
    ['label' => 'Editer', 'href' => $adminBase . '/roles/' . (int) ($role['id'] ?? 0) . '/edit', 'class' => 'btn btn-primary btn-sm'],
];

ob_start();
?>
<section class="row g-3">
    <div class="col-12 col-xl-4"><?php require __DIR__ . '/partials/role-summary.php'; ?></div>
    <div class="col-12 col-xl-8">
        <section class="card h-100">
            <div class="card-header bg-transparent border-0 pt-3"><h3 class="h6 mb-0">Permissions actives</h3></div>
            <div class="card-body pt-2">
                <?php if (($activePermissions ?? []) === []): ?>
                    <p class="small text-body-secondary mb-0">Aucune permission active.</p>
                <?php else: ?>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($activePermissions as $permission): ?>
                            <span class="badge text-bg-dark"><?= htmlspecialchars((string) ($permission['slug'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</section>

<section class="card">
    <div class="card-header bg-transparent border-0 pt-3"><h3 class="h6 mb-0">Utilisateurs associes</h3></div>
    <div class="card-body pt-2">
        <?php if (($linkedUsers ?? []) === []): ?>
            <p class="small text-body-secondary mb-0">Aucun utilisateur assigne.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>Username</th><th>Email</th><th>Statut</th></tr></thead>
                    <tbody>
                    <?php foreach ($linkedUsers as $userRow): ?>
                        <tr>
                            <td><?= htmlspecialchars((string) ($userRow['username'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) ($userRow['email'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= ((int) ($userRow['is_active'] ?? 0) === 1) ? '<span class="badge text-bg-success">Actif</span>' : '<span class="badge text-bg-secondary">Inactif</span>' ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>
<script src="/assets/js/catmin-roles.js?v=2"></script>
<?php
$content = (string) ob_get_clean();
require CATMIN_ADMIN . '/views/layouts/admin.php';
