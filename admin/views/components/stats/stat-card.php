<?php
declare(strict_types=1);

$title = isset($title) ? (string) $title : 'Stat';
$value = isset($value) ? (string) $value : '0';
$hint = isset($hint) ? (string) $hint : '';
$tone = isset($tone) ? (string) $tone : 'neutral';
$icon = isset($icon) ? (string) $icon : '';
$toneClass = match ($tone) {
    'success' => 'text-success',
    'warning' => 'text-warning',
    'danger' => 'text-danger',
    'info' => 'text-info',
    default => 'text-body-secondary',
};
$cardToneClass = match ($tone) {
    'success' => 'cat-stat-card--success',
    'warning' => 'cat-stat-card--warning',
    'danger' => 'cat-stat-card--danger',
    'info' => 'cat-stat-card--info',
    default => 'cat-stat-card--neutral',
};
?>
<article class="card cat-stat-card <?= htmlspecialchars($cardToneClass, ENT_QUOTES, 'UTF-8') ?>">
    <div class="card-body">
        <?php if ($icon !== ''): ?>
            <div class="cat-stat-icon-wrap mb-3">
                <i class="bi <?= htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') ?>"></i>
            </div>
        <?php endif; ?>
        <p class="cat-stat-title mb-2"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></p>
        <p class="h3 mb-1 d-flex align-items-center gap-2">
            <span><?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?></span>
            <span class="cat-stat-trend"><i class="bi bi-arrow-up-right"></i></span>
        </p>
        <?php if ($hint !== ''): ?>
            <p class="small mb-0 cat-stat-hint <?= $toneClass ?>"><?= htmlspecialchars($hint, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
    </div>
</article>
