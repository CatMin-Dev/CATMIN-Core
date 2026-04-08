<?php
declare(strict_types=1);

$rows = isset($rows) && is_array($rows) ? $rows : [];
?>
<section class="card">
    <div class="card-body">
        <?php require dirname(__DIR__) . '/toolbars/basic.php'; ?>
        <?php if ($rows === []): ?>
            <?php
            $title = 'Aucun resultat';
            $description = 'Aucune ligne disponible pour le moment.';
            require dirname(__DIR__) . '/empty-states/basic.php';
            ?>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                    <tr>
                        <th>Element</th>
                        <th>Etat</th>
                        <th>Date</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($rows as $row): ?>
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
