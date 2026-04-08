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
                $title = 'Aucun role';
                $description = 'Cree un premier role pour demarrer.';
                require CATMIN_ADMIN . '/views/components/empty-states/basic.php';
                ?>
            </div>
        <?php else: ?>
            <table class="table table-hover align-middle mb-0">
                <thead>
                <tr>
                    <th>Role</th>
                    <th>Description</th>
                    <th>Utilisateurs</th>
                    <th>Niveau</th>
                    <th class="text-end">Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <?php $critical = ((int) ($row['is_system'] ?? 0) === 1) || ((string) ($row['slug'] ?? '') === 'super-admin'); ?>
                    <tr>
                        <td>
                            <p class="mb-0 fw-semibold"><?= htmlspecialchars((string) ($row['name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                            <small class="text-body-secondary"><?= htmlspecialchars((string) ($row['slug'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></small>
                        </td>
                        <td><small class="text-body-secondary">Role CATMIN</small></td>
                        <td><span class="badge text-bg-light border"><?= (int) ($row['users_count'] ?? 0) ?></span></td>
                        <td><?= $critical ? '<span class="badge text-bg-danger">Critique</span>' : '<span class="badge text-bg-secondary">Standard</span>' ?></td>
                        <td class="text-end">
                            <a href="<?= htmlspecialchars((string) ($adminBase ?? '/admin') . '/roles/' . (int) ($row['id'] ?? 0), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-secondary">Voir</a>
                            <a href="<?= htmlspecialchars((string) ($adminBase ?? '/admin') . '/roles/' . (int) ($row['id'] ?? 0) . '/edit', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-primary">Editer</a>
                            <?php if (!$critical): ?>
                                <form method="post" action="<?= htmlspecialchars((string) ($adminBase ?? '/admin') . '/roles/' . (int) ($row['id'] ?? 0) . '/delete', ENT_QUOTES, 'UTF-8') ?>" class="d-inline">
                                    <input type="hidden" name="_csrf" value="<?= $csrfToken ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Supprimer ce rôle ?');">Supprimer</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</section>
