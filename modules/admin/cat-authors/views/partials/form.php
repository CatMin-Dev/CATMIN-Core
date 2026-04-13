<?php

declare(strict_types=1);

$tr = isset($tr) && is_array($tr) ? $tr : [];
$adminBase = isset($adminBase) ? (string) $adminBase : '/admin';
$action = isset($action) ? (string) $action : ($adminBase . '/modules/author-bridge/profile/create');
$submitLabel = isset($submitLabel) ? (string) $submitLabel : (string) ($tr['btn_create'] ?? 'Ajouter un auteur');
$cancelHref = isset($cancelHref) ? (string) $cancelHref : ($adminBase . '/modules/author-bridge');
$csrf = isset($csrf) ? (string) $csrf : '';
$mode = isset($mode) ? (string) $mode : 'create';
$profile = isset($profile) && is_array($profile) ? $profile : [];
$users = isset($users) && is_array($users) ? $users : [];

$visibilities = [
    'public' => (string) ($tr['visibility_public'] ?? 'Public'),
    'private' => (string) ($tr['visibility_private'] ?? 'Prive'),
    'unlisted' => (string) ($tr['visibility_unlisted'] ?? 'Non liste'),
];

$socialNetworks = [
    'twitter' => ['label' => 'X / Twitter', 'icon' => 'twitter-x'],
    'linkedin' => ['label' => 'LinkedIn', 'icon' => 'linkedin'],
    'github' => ['label' => 'GitHub', 'icon' => 'github'],
    'instagram' => ['label' => 'Instagram', 'icon' => 'instagram'],
    'mastodon' => ['label' => 'Mastodon', 'icon' => 'mastodon'],
    'facebook' => ['label' => 'Facebook', 'icon' => 'facebook'],
    'youtube' => ['label' => 'YouTube', 'icon' => 'youtube'],
    'tiktok' => ['label' => 'TikTok', 'icon' => 'music-note-beamed'],
    'telegram' => ['label' => 'Telegram', 'icon' => 'send'],
    'threads' => ['label' => 'Threads', 'icon' => 'at'],
    'bluesky' => ['label' => 'Bluesky', 'icon' => 'cloud'],
];

$rawSocials = [];
if (isset($profile['socials_json']) && is_string($profile['socials_json']) && $profile['socials_json'] !== '') {
    $decoded = json_decode($profile['socials_json'], true);
    if (is_array($decoded)) {
        $rawSocials = $decoded;
    }
}

$socialMap = [];
foreach ($rawSocials as $social) {
    if (!is_array($social)) {
        continue;
    }
    $network = strtolower(trim((string) ($social['network'] ?? '')));
    $url = trim((string) ($social['url'] ?? ''));
    if ($network === '' || $url === '' || !isset($socialNetworks[$network])) {
        continue;
    }
    $socialMap[$network] = $url;
}
?>

<form
  method="post"
  action="<?= htmlspecialchars($action, ENT_QUOTES, 'UTF-8') ?>"
  class="card"
  data-social-active-label="<?= htmlspecialchars((string) ($tr['social_active'] ?? 'Actif'), ENT_QUOTES, 'UTF-8') ?>"
  data-social-inactive-label="<?= htmlspecialchars((string) ($tr['social_inactive'] ?? 'Inactif'), ENT_QUOTES, 'UTF-8') ?>"
>
  <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
  <?php if ($mode === 'edit'): ?>
    <input type="hidden" name="id" value="<?= (int) ($profile['id'] ?? 0) ?>">
  <?php endif; ?>

  <div class="card-body">
    <div class="row g-3">
      <div class="col-12">
        <label class="form-label"><?= htmlspecialchars((string) ($tr['linked_user'] ?? 'Compte admin'), ENT_QUOTES, 'UTF-8') ?> <span class="text-danger">*</span></label>
        <select class="form-select" name="user_id" required>
          <option value=""><?= htmlspecialchars((string) ($tr['select_user'] ?? 'Selectionnez un compte'), ENT_QUOTES, 'UTF-8') ?></option>
          <?php foreach ($users as $account): ?>
            <?php $accountId = (int) ($account['id'] ?? 0); ?>
            <option value="<?= $accountId ?>" <?= $accountId === (int) ($profile['user_id'] ?? 0) ? 'selected' : '' ?>>
              <?= htmlspecialchars((string) ($account['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-12 col-md-6">
        <label class="form-label"><?= htmlspecialchars((string) ($tr['first_name'] ?? 'Prenom'), ENT_QUOTES, 'UTF-8') ?> *</label>
        <input class="form-control" type="text" name="first_name" data-author-first-name maxlength="120" required value="<?= htmlspecialchars((string) ($profile['first_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label"><?= htmlspecialchars((string) ($tr['last_name'] ?? 'Nom'), ENT_QUOTES, 'UTF-8') ?> *</label>
        <input class="form-control" type="text" name="last_name" data-author-last-name maxlength="120" required value="<?= htmlspecialchars((string) ($profile['last_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
      </div>

      <div class="col-12 col-md-6">
        <label class="form-label"><?= htmlspecialchars((string) ($tr['display_name'] ?? 'Nom d affichage'), ENT_QUOTES, 'UTF-8') ?> *</label>
        <input class="form-control" type="text" name="display_name" data-author-display-name maxlength="160" required value="<?= htmlspecialchars((string) ($profile['display_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label"><?= htmlspecialchars((string) ($tr['slug'] ?? 'Slug'), ENT_QUOTES, 'UTF-8') ?> *</label>
        <input class="form-control" type="text" name="slug" data-author-slug maxlength="200" value="<?= htmlspecialchars((string) ($profile['slug'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
      </div>

      <div class="col-12 col-md-6">
        <label class="form-label"><?= htmlspecialchars((string) ($tr['visibility'] ?? 'Visibilite'), ENT_QUOTES, 'UTF-8') ?></label>
        <select class="form-select" name="visibility">
          <?php foreach ($visibilities as $key => $label): ?>
            <option value="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" <?= $key === (string) ($profile['visibility'] ?? 'public') ? 'selected' : '' ?>>
              <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-12">
        <label class="form-label"><?= htmlspecialchars((string) ($tr['bio'] ?? 'Biographie'), ENT_QUOTES, 'UTF-8') ?></label>
        <textarea class="form-control" name="bio" rows="4"><?= htmlspecialchars((string) ($profile['bio'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
      </div>

      <div class="col-12">
        <h3 class="h6 mb-2"><?= htmlspecialchars((string) ($tr['socials'] ?? 'Reseaux sociaux'), ENT_QUOTES, 'UTF-8') ?></h3>
        <div class="row g-2">
          <?php foreach ($socialNetworks as $networkKey => $meta): ?>
            <?php $value = (string) ($socialMap[$networkKey] ?? ''); ?>
            <div class="col-12 col-xl-6">
              <div class="input-group" data-author-social-row>
                <span class="input-group-text">
                  <i class="bi bi-<?= htmlspecialchars((string) $meta['icon'], ENT_QUOTES, 'UTF-8') ?> me-1"></i>
                  <?= htmlspecialchars((string) $meta['label'], ENT_QUOTES, 'UTF-8') ?>
                </span>
                <input type="hidden" name="social_network[]" value="<?= htmlspecialchars($networkKey, ENT_QUOTES, 'UTF-8') ?>">
                <input
                  class="form-control"
                  type="url"
                  name="social_url[]"
                  value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>"
                  placeholder="https://..."
                  data-author-social-url
                >
                <span
                  class="input-group-text cat-author-social-state <?= $value !== '' ? 'text-bg-success' : 'text-bg-danger' ?>"
                  data-author-social-state
                >
                  <?= htmlspecialchars((string) ($value !== '' ? ($tr['social_active'] ?? 'Actif') : ($tr['social_inactive'] ?? 'Inactif')), ENT_QUOTES, 'UTF-8') ?>
                </span>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="card-footer d-flex align-items-center gap-2">
    <button class="btn btn-primary" type="submit"><?= htmlspecialchars($submitLabel, ENT_QUOTES, 'UTF-8') ?></button>
    <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($cancelHref, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($tr['btn_cancel'] ?? 'Annuler'), ENT_QUOTES, 'UTF-8') ?></a>
  </div>
</form>
