<?php
declare(strict_types=1);
?>
<section class="row g-3">
    <?php foreach ($stats as $stat): ?>
        <div class="col-12 col-md-6 col-xl-3">
            <?php
            $title = (string) ($stat['title'] ?? 'Stat');
            $value = (string) ($stat['value'] ?? '0');
            $hint = (string) ($stat['hint'] ?? '');
            $tone = (string) ($stat['tone'] ?? 'neutral');
            require CATMIN_ADMIN . '/views/components/stats/stat-card.php';
            ?>
        </div>
    <?php endforeach; ?>
</section>
