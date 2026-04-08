<?php
declare(strict_types=1);

$label = isset($label) ? (string) $label : 'Etat';
$variant = isset($variant) ? (string) $variant : 'neutral';
$class = match ($variant) {
    'success' => 'text-bg-success',
    'warning' => 'text-bg-warning',
    'danger' => 'text-bg-danger',
    'info' => 'text-bg-info',
    'accent' => 'cat-badge-accent',
    default => 'text-bg-secondary',
};
?>
<span class="badge rounded-pill <?= $class ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
