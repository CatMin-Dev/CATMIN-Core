<?php

declare(strict_types=1);
?>
<section class="card h-100">
    <div class="card-header bg-transparent border-0 pt-3">
        <h3 class="h6 mb-0">Activite recente</h3>
    </div>
    <div class="card-body pt-2">
        <?php if ($activity === []): ?>
            <?php
            $title = 'Aucun log recent';
            $description = 'Les evenements admin apparaitront ici.';
            require CATMIN_ADMIN . '/views/components/empty-states/basic.php';
            ?>
        <?php else: ?>
            <ul class="list-group list-group-flush">
                <?php foreach ($activity as $item): ?>
                    <li class="list-group-item px-0 d-flex justify-content-between align-items-start gap-3">
                        <div>
                            <p class="mb-0 fw-semibold"><?= htmlspecialchars((string) ($item['title'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
                            <small class="text-body-secondary"><?= htmlspecialchars((string) ($item['meta'] ?? ''), ENT_QUOTES, 'UTF-8') ?></small>
                        </div>
                        <?php
                        $label = (string) ($item['status'] ?? 'INFO');
                        $variant = (string) ($item['variant'] ?? 'info');
                        require CATMIN_ADMIN . '/views/components/badges/status.php';
                        ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</section>
