<?php

declare(strict_types=1);

/**
 * Widget: author_identity_inline
 * Usage: render_widget('author_identity_inline', ['profile' => $profileArray])
 * One-liner author identity for bylines (e.g. "Par Jean Dupont").
 */

$profile = isset($profile) && is_array($profile) ? $profile : null;
$prefix  = isset($prefix) ? trim((string) $prefix) : 'Par';

if ($profile === null) {
    return;
}

$name = htmlspecialchars((string) ($profile['display_name'] ?? ''), ENT_QUOTES, 'UTF-8');

echo '<span class="author-identity-inline text-body-secondary small">';
if ($prefix !== '') {
    echo htmlspecialchars($prefix, ENT_QUOTES, 'UTF-8') . ' ';
}
echo '<strong>' . $name . '</strong>';
echo '</span>';
