<?php

declare(strict_types=1);
?>
<section class="card h-100">
    <div class="card-header bg-transparent border-0 pt-3">
        <h3 class="h6 mb-0">Evenements recents</h3>
    </div>
    <div class="card-body pt-2">
        <?php if ($events === []): ?>
            <?php
            $title = 'Aucun evenement';
            $description = 'Les evenements systeme seront visibles ici.';
            require CATMIN_ADMIN . '/views/components/empty-states/basic.php';
            ?>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                    <tr>
                        <th>Element</th>
                        <th>Etat</th>
                        <th>Date</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($events as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars((string) ($row['label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) ($row['status'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) ($row['date'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>
