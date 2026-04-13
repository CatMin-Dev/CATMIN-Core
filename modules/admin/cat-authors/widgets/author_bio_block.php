<?php

declare(strict_types=1);

/**
 * Widget: author_bio_block
 * Usage: render_widget('author_bio_block', ['profile' => $profileArray])
 * Full bio block for single article/page view.
 */

$profile = isset($profile) && is_array($profile) ? $profile : null;

if ($profile === null || trim((string) ($profile['bio'] ?? '')) === '') {
    return;
}

$name    = htmlspecialchars((string) ($profile['display_name'] ?? ''), ENT_QUOTES, 'UTF-8');
$bio     = nl2br(htmlspecialchars((string) $profile['bio'], ENT_QUOTES, 'UTF-8'));
$website = filter_var((string) ($profile['website_url'] ?? ''), FILTER_VALIDATE_URL) ?: '';
?>
<div class="author-bio-block border rounded p-3 my-3 bg-body-secondary">
  <div class="d-flex align-items-center gap-2 mb-2">
    <span class="rounded-circle bg-secondary d-inline-flex align-items-center justify-content-center text-white"
          style="width:36px;height:36px;flex-shrink:0">
      <i class="bi bi-person-fill"></i>
    </span>
    <strong><?= $name ?></strong>
  </div>
  <p class="small mb-1"><?= $bio ?></p>
  <?php if ($website !== ''): ?>
    <a href="<?= htmlspecialchars($website, ENT_QUOTES, 'UTF-8') ?>" class="small text-decoration-none" target="_blank" rel="noopener noreferrer">
      <i class="bi bi-globe me-1"></i><?= htmlspecialchars($website, ENT_QUOTES, 'UTF-8') ?>
    </a>
  <?php endif; ?>
</div>
