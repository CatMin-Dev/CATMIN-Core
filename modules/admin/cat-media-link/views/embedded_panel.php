<?php

declare(strict_types=1);

$state = isset($state) && is_array($state) ? $state : [];
$assets = isset($state['assets']) && is_array($state['assets']) ? $state['assets'] : [];
$preview = isset($preview) && is_array($preview) ? $preview : ['featured' => null];
$tr = isset($tr) && is_array($tr) ? $tr : [];

$featuredId = (int) (($preview['featured']['media_id'] ?? 0));
?>
<div class="cat-media-panel">
  <label class="form-label fw-semibold">
    <i class="bi bi-images me-1"></i>
    <?= htmlspecialchars((string) ($tr['title'] ?? 'Media'), ENT_QUOTES, 'UTF-8') ?>
  </label>

  <div class="row g-2">
    <div class="col-12 col-md-4">
      <label class="form-label"><?= htmlspecialchars((string) ($tr['featured_media'] ?? 'Featured media'), ENT_QUOTES, 'UTF-8') ?></label>
      <input class="form-control" type="number" min="0" name="featured_media_id" value="<?= $featuredId ?>">
    </div>
    <div class="col-12 col-md-4">
      <label class="form-label"><?= htmlspecialchars((string) ($tr['gallery_media'] ?? 'Gallery IDs'), ENT_QUOTES, 'UTF-8') ?></label>
      <input class="form-control" name="gallery_media_ids" placeholder="12,34,90">
    </div>
    <div class="col-12 col-md-4">
      <label class="form-label"><?= htmlspecialchars((string) ($tr['social_media'] ?? 'Social media'), ENT_QUOTES, 'UTF-8') ?></label>
      <input class="form-control" type="number" min="0" name="social_media_id" value="0">
    </div>
  </div>

  <?php if ($assets !== []): ?>
    <div class="mt-2 small text-body-secondary">
      <?= htmlspecialchars((string) ($tr['explorer'] ?? 'Media explorer'), ENT_QUOTES, 'UTF-8') ?>: <?= count($assets) ?>
    </div>
  <?php endif; ?>
</div>
