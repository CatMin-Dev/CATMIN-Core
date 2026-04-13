<?php

declare(strict_types=1);

$links = isset($links) && is_array($links) ? $links : [];
?>
<div class="cat-widget-linked-media-panel">
  <h3 class="h6 mb-2">Linked media</h3>
  <ul class="list-group list-group-flush">
    <?php foreach ($links as $row): ?>
      <li class="list-group-item px-0">#<?= (int) ($row['media_id'] ?? 0) ?> · <?= htmlspecialchars((string) ($row['link_type'] ?? 'gallery'), ENT_QUOTES, 'UTF-8') ?></li>
    <?php endforeach; ?>
  </ul>
</div>
