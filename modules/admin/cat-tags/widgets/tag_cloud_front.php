<?php

declare(strict_types=1);

$tags = isset($tags) && is_array($tags) ? $tags : [];
?>
<div class="d-flex flex-wrap gap-2">
<?php foreach ($tags as $tag): ?>
  <?php $usage = max(1, min(10, (int) ($tag['usage_count'] ?? 1))); ?>
  <?php $sizeClass = $usage >= 8 ? 'fs-4' : ($usage >= 6 ? 'fs-5' : ($usage >= 4 ? 'fs-6' : 'small')); ?>
  <span class="<?= $sizeClass ?>"><?= htmlspecialchars((string) ($tag['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
<?php endforeach; ?>
</div>
