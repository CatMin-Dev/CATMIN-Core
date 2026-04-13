<?php

declare(strict_types=1);

$items = isset($items) && is_array($items) ? $items : [];
if ($items === []) {
    return;
}
?>
<div class="d-flex flex-wrap gap-2 cat-widget-gallery-block">
  <?php foreach ($items as $item): ?>
    <?php $url = (string) ($item['public_url'] ?? ''); if ($url === '') continue; ?>
    <img src="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>" alt="" class="rounded" style="width:120px;height:80px;object-fit:cover;">
  <?php endforeach; ?>
</div>
