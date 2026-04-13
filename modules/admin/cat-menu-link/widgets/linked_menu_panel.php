<?php

declare(strict_types=1);

$items = isset($items) && is_array($items) ? $items : [];
?>
<ul class="list-group list-group-flush">
  <?php foreach ($items as $row): ?>
    <li class="list-group-item px-0">#<?= (int) ($row['id'] ?? 0) ?> · <?= htmlspecialchars((string) ($row['menu_key'] ?? ''), ENT_QUOTES, 'UTF-8') ?></li>
  <?php endforeach; ?>
</ul>
