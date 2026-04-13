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
$slug    = htmlspecialchars((string) ($profile['slug'] ?? ''), ENT_QUOTES, 'UTF-8');

$socialsRaw = $profile['socials_json'] ?? null;
$socialsDecoded = is_string($socialsRaw) && $socialsRaw !== '' ? (json_decode($socialsRaw, true) ?: []) : (is_array($socialsRaw) ? $socialsRaw : []);
$socials = [];
if (is_array($socialsDecoded)) {
  $isAssoc = array_keys($socialsDecoded) !== range(0, count($socialsDecoded) - 1);
  if ($isAssoc) {
    foreach ($socialsDecoded as $network => $url) {
      $network = strtolower(trim((string) $network));
      $url = trim((string) $url);
      if ($network === '' || $url === '') {
        continue;
      }
      $socials[] = ['network' => $network, 'url' => $url];
    }
  } else {
    foreach ($socialsDecoded as $item) {
      if (!is_array($item)) {
        continue;
      }
      $network = strtolower(trim((string) ($item['network'] ?? '')));
      $url = trim((string) ($item['url'] ?? ''));
      if ($network === '' || $url === '') {
        continue;
      }
      $socials[] = ['network' => $network, 'url' => $url];
    }
  }
}

$socialIcons = [
    'twitter'   => 'twitter-x',
    'linkedin'  => 'linkedin',
    'github'    => 'github',
    'instagram' => 'instagram',
    'mastodon'  => 'mastodon',
  'facebook'  => 'facebook',
  'youtube'   => 'youtube',
  'tiktok'    => 'music-note-beamed',
  'telegram'  => 'send',
  'threads'   => 'at',
  'bluesky'   => 'cloud',
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
    <?php if ($socials !== []): ?>
      <div class="d-flex flex-wrap gap-2">
        <?php foreach ($socials as $social): ?>
          <?php $icon = $socialIcons[$social['network']] ?? 'link-45deg'; ?>
          <a href="<?= htmlspecialchars((string) $social['url'], ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener noreferrer">
            <i class="bi bi-<?= htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') ?>"></i>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
