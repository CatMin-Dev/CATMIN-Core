<?php

declare(strict_types=1);
$events = isset($events) && is_array($events) ? $events : [];
?>
<section class="card h-100">
    <div class="card-header bg-transparent border-0 pt-3"><h3 class="h6 mb-0">Historique activite</h3></div>
    <div class="card-body pt-2">
        <?php if ($events === []): ?>
            <p class="small text-body-secondary mb-0">Aucun evenement.</p>
        <?php else: ?>
            <ul class="list-group list-group-flush">
                <?php foreach ($events as $event): ?>
                    <li class="list-group-item px-0 d-flex justify-content-between">
                        <span><?= htmlspecialchars((string) ($event['event_type'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
                        <small class="text-body-secondary"><?= htmlspecialchars((string) ($event['created_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></small>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</section>
