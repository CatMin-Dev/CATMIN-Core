<?php

declare(strict_types=1);

$preview = isset($preview) && is_array($preview) ? $preview : [];
$title = htmlspecialchars((string) ($preview['og_title'] ?? $preview['title'] ?? ''), ENT_QUOTES, 'UTF-8');
$desc = htmlspecialchars((string) ($preview['og_description'] ?? $preview['description'] ?? ''), ENT_QUOTES, 'UTF-8');
$url = htmlspecialchars((string) ($preview['url'] ?? '/'), ENT_QUOTES, 'UTF-8');
?>
<div class="border rounded p-3 bg-light-subtle">
  <p class="small text-body-secondary mb-1"><?= $url ?></p>
  <p class="fw-semibold mb-1"><?= $title ?></p>
  <p class="small mb-0 text-body-secondary"><?= $desc ?></p>
</div>
