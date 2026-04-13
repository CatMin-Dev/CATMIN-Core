<?php

declare(strict_types=1);

$tags = isset($tags) && is_array($tags) ? $tags : [];
?>
<div class="d-flex flex-wrap gap-2">
<?php foreach ($tags as $tag): ?>
  <span class="badge rounded-pill text-bg-secondary"><?= htmlspecialchars((string) ($tag['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
<?php endforeach; ?>
</div>
