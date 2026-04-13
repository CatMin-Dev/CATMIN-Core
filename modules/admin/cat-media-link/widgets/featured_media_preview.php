<?php

declare(strict_types=1);

$media = isset($media) && is_array($media) ? $media : [];
$url = (string) ($media['public_url'] ?? '');
if ($url === '') {
    return;
}
?>
<div class="cat-widget-featured-media">
  <img src="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string) ($media['alt_text'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="img-fluid rounded">
</div>
