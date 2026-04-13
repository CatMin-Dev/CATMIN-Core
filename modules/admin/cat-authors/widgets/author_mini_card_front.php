<?php

declare(strict_types=1);

/**
 * Widget: author_mini_card_front
 * Usage: render_widget('author_mini_card_front', ['profile' => $profileArray])
 * Compact inline author block for front-end article listings.
 */

$profile = isset($profile) && is_array($profile) ? $profile : null;

if ($profile === null) {
    return;
}

$name = htmlspecialchars((string) ($profile['display_name'] ?? ''), ENT_QUOTES, 'UTF-8');
$slug = htmlspecialchars((string) ($profile['slug'] ?? ''), ENT_QUOTES, 'UTF-8');

echo '<span class="author-mini-card d-inline-flex align-items-center gap-1">';
echo '<span class="rounded-circle bg-secondary d-inline-flex align-items-center justify-content-center text-white" style="width:24px;height:24px;flex-shrink:0"><i class="bi bi-person-fill" style="font-size:.7rem"></i></span>';
echo '<span class="small fw-semibold">' . $name . '</span>';
echo '</span>';
