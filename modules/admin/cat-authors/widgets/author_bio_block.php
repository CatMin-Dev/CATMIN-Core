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
  'twitter' => 'twitter-x',
  'linkedin' => 'linkedin',
  'github' => 'github',
  'instagram' => 'instagram',
  'mastodon' => 'mastodon',
  'facebook' => 'facebook',
  'youtube' => 'youtube',
  'tiktok' => 'music-note-beamed',
  'telegram' => 'send',
  'threads' => 'at',
  'bluesky' => 'cloud',
];
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
  <?php if ($socials !== []): ?>
    <div class="d-flex flex-wrap gap-2 mt-2">
      <?php foreach ($socials as $social): ?>
        <?php $icon = $socialIcons[$social['network']] ?? 'link-45deg'; ?>
        <a href="<?= htmlspecialchars((string) $social['url'], ENT_QUOTES, 'UTF-8') ?>" class="badge text-bg-light text-decoration-none" target="_blank" rel="noopener noreferrer">
          <i class="bi bi-<?= htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') ?> me-1"></i><?= htmlspecialchars((string) $social['network'], ENT_QUOTES, 'UTF-8') ?>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
