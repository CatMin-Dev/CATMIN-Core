<?php

declare(strict_types=1);

/**
 * Widget: author_badge_admin
 * Usage: render_widget('author_badge_admin', ['profile' => $profileArray])
 * Returns a small admin badge with author name and linked account indicator.
 */

$profile = isset($profile) && is_array($profile) ? $profile : null;

if ($profile === null) {
    echo '<span class="badge text-bg-secondary">—</span>';
    return;
}

$name     = htmlspecialchars((string) ($profile['display_name'] ?? ''), ENT_QUOTES, 'UTF-8');
$username = htmlspecialchars((string) ($profile['username'] ?? ''), ENT_QUOTES, 'UTF-8');

echo '<span class="badge text-bg-info" title="' . ($username !== '' ? 'Compte: ' . $username : 'Sans compte lié') . '">';
echo '<i class="bi bi-person-badge me-1"></i>';
echo $name;
if ($username !== '') {
    echo ' <span class="opacity-75 small">@' . $username . '</span>';
}
echo '</span>';
