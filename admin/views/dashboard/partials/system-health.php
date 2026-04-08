<?php
declare(strict_types=1);
?>
<section class="card h-100">
    <div class="card-header bg-transparent border-0 pt-3">
        <h3 class="h6 mb-0">Sante systeme</h3>
    </div>
    <div class="card-body pt-2">
        <ul class="list-group list-group-flush">
            <?php foreach ($health as $line): ?>
                <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                    <span><?= htmlspecialchars((string) ($line['label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
                    <?php
                    $label = (string) ($line['value'] ?? '-');
                    $variant = (string) ($line['variant'] ?? 'neutral');
                    require CATMIN_ADMIN . '/views/components/badges/status.php';
                    ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
