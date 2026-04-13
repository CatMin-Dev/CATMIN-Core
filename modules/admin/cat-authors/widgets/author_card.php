<?php

declare(strict_types=1);

/**
 * Widget: author_card
 * Usage: render_widget('author_card', ['profile' => $profileArray, 'size' => 'md'])
 * Returns a Bootstrap card block for an author profile (admin or front use).
 */

$profile = isset($profile) && is_array($profile) ? $profile : null;
$size    = isset($size) && in_array($size, ['sm', 'md', 'lg'], true) ? $size : 'md';

if ($profile === null) {
    return;
}

$name    = htmlspecialchars((string) ($profile['display_name'] ?? ''), ENT_QUOTES, 'UTF-8');
$bio     = htmlspecialchars((string) ($profile['bio'] ?? ''), ENT_QUOTES, 'UTF-8');
$website = filter_var((string) ($profile['website_url'] ?? ''), FILTER_VALIDATE_URL) ?: '';
$slug    = htmlspecialchars((string) ($profile['slug'] ?? ''), ENT_QUOTES, 'UTF-8');

$socialsRaw = $profile['socials_json'] ?? null;
$socials    = is_string($socialsRaw) && $socialsRaw !== '' ? (json_decode($socialsRaw, true) ?: []) : [];

$socialIcons = [
    'twitter'   => 'twitter-x',
    'linkedin'  => 'linkedin',
    'github'    => 'github',
    'instagram' => 'instagram',
    'mastodon'  => 'mastodon',
];

?>
<div class="card author-card author-card--<?= $size ?>">
  <div class="card-body">
    <div class="d-flex align-items-center gap-3 mb-2">
      <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center"
           style="width:48px;height:48px;flex-shrink:0">
        <i class="bi bi-person-fill text-white fs-4"></i>
      </div>
      <div>
        <strong class="d-block"><?= $name ?></strong>
        <?php if ($slug !== ''): ?>
          <small class="text-body-secondary">@<?= $slug ?></small>
        <?php endif; ?>
      </div>
    </div>
    <?php if ($bio !== ''): ?>
      <p class="small mb-2"><?= $bio ?></p>
    <?php endif; ?>
    <?php if ($website !== '' || $socials !== []): ?>
      <div class="d-flex flex-wrap gap-2">
        <?php if ($website !== ''): ?>
          <a href="<?= htmlspecialchars($website, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener noreferrer">
            <i class="bi bi-globe me-1"></i>Site
          </a>
        <?php endif; ?>
        <?php foreach ($socialIcons as $key => $icon): ?>
          <?php if (!empty($socials[$key])): ?>
            <a href="<?= htmlspecialchars((string) $socials[$key], ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener noreferrer">
              <i class="bi bi-<?= $icon ?>"></i>
            </a>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
