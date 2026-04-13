<?php

declare(strict_types=1);

$categories = isset($categories) && is_array($categories) ? $categories : [];
?>
<div class="d-flex flex-wrap gap-2">
<?php foreach ($categories as $cat): ?>
  <span class="badge rounded-pill text-bg-light border"><?= htmlspecialchars((string) ($cat['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
<?php endforeach; ?>
</div>
