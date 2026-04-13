<?php

declare(strict_types=1);

use Core\security\CsrfManager;

$dashboard = isset($dashboard) && is_array($dashboard) ? $dashboard : ['total' => 0, 'profiles' => [], 'users' => [], 'available_users' => []];
$profiles = isset($dashboard['profiles']) && is_array($dashboard['profiles']) ? $dashboard['profiles'] : [];
$users = isset($dashboard['users']) && is_array($dashboard['users']) ? $dashboard['users'] : [];
$availableUsers = isset($dashboard['available_users']) && is_array($dashboard['available_users']) ? $dashboard['available_users'] : [];
$total = (int) ($dashboard['total'] ?? 0);
$message = isset($message) ? trim((string) $message) : '';
$messageType = isset($messageType) ? trim((string) $messageType) : 'info';
$tr = isset($tr) && is_array($tr) ? $tr : [];
$adminBase = isset($adminBase) ? (string) $adminBase : '/admin';
$csrf = htmlspecialchars((new CsrfManager())->token(), ENT_QUOTES, 'UTF-8');

$pageTitle = (string) ($tr['title'] ?? 'Auteurs');
$pageDescription = (string) ($tr['description'] ?? 'Gestion des comptes auteurs');
$activeNav = 'author-bridge';
$breadcrumbs = [
    ['label' => __('common.admin'), 'href' => $adminBase . '/'],
    ['label' => 'Organisation'],
    ['label' => $pageTitle],
];

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

$normalizeSocials = static function (mixed $raw) use ($socialNetworks): array {
    if (is_string($raw) && $raw !== '') {
        $decoded = json_decode($raw, true);
        $raw = is_array($decoded) ? $decoded : [];
    }

    if (!is_array($raw)) {
        return [];
    }

    $isAssoc = array_keys($raw) !== range(0, count($raw) - 1);
    $socials = [];

    if ($isAssoc) {
        foreach ($raw as $network => $url) {
            $network = strtolower(trim((string) $network));
            $url = trim((string) $url);
            if ($network === '' || $url === '' || !isset($socialNetworks[$network])) {
                continue;
            }
            $socials[] = ['network' => $network, 'url' => $url];
        }
        return $socials;
    }

    foreach ($raw as $item) {
        if (!is_array($item)) {
            continue;
        }
        $network = strtolower(trim((string) ($item['network'] ?? '')));
        $url = trim((string) ($item['url'] ?? ''));
        if ($network === '' || $url === '' || !isset($socialNetworks[$network])) {
            continue;
        }
        $socials[] = ['network' => $network, 'url' => $url];
    }

    return $socials;
};

ob_start();
?>

<?php if ($message !== ''): ?>
<div class="alert alert-<?= htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8') ?> mb-3">
  <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
</div>
<?php endif; ?>

<div class="row g-3 mb-3">
  <div class="col-12 col-md-4">
    <div class="card h-100"><div class="card-body">
      <p class="small text-body-secondary mb-1"><?= htmlspecialchars((string) ($tr['total_profiles'] ?? 'Comptes auteurs actifs'), ENT_QUOTES, 'UTF-8') ?></p>
      <p class="h3 mb-0"><?= $total ?></p>
    </div></div>
  </div>
  <div class="col-12 col-md-4">
    <div class="card h-100"><div class="card-body">
      <p class="small text-body-secondary mb-1"><?= htmlspecialchars((string) ($tr['total_users'] ?? 'Comptes admin'), ENT_QUOTES, 'UTF-8') ?></p>
      <p class="h3 mb-0"><?= count($users) ?></p>
    </div></div>
  </div>
  <div class="col-12 col-md-4">
    <div class="card h-100"><div class="card-body">
      <p class="small text-body-secondary mb-1"><?= htmlspecialchars((string) ($tr['available_accounts'] ?? 'Comptes activables'), ENT_QUOTES, 'UTF-8') ?></p>
      <p class="h3 mb-0"><?= count($availableUsers) ?></p>
    </div></div>
  </div>
</div>

<div class="card mb-3">
  <div class="card-body d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
    <div>
      <h2 class="h5 mb-1"><?= htmlspecialchars((string) ($tr['listing_title'] ?? 'Comptes auteurs'), ENT_QUOTES, 'UTF-8') ?></h2>
      <p class="text-body-secondary mb-0"><?= htmlspecialchars((string) ($tr['intro'] ?? 'Chaque auteur enrichit un compte admin existant avec des metadonnees editoriales.'), ENT_QUOTES, 'UTF-8') ?></p>
    </div>
    <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#createAuthorModal" <?= $availableUsers === [] ? 'disabled' : '' ?>>
      <i class="bi bi-person-plus me-1"></i>
      <?= htmlspecialchars((string) ($tr['btn_add_author'] ?? 'Activer un auteur'), ENT_QUOTES, 'UTF-8') ?>
    </button>
  </div>
</div>

<?php if ($availableUsers === []): ?>
<div class="alert alert-secondary mb-3">
  <?= htmlspecialchars((string) ($tr['no_available_users'] ?? 'Tous les comptes admin ont deja une fiche auteur.'), ENT_QUOTES, 'UTF-8') ?>
</div>
<?php endif; ?>

<div class="card">
  <div class="card-body p-0">
    <?php if ($profiles === []): ?>
      <div class="p-4 text-body-secondary small"><?= htmlspecialchars((string) ($tr['no_profiles'] ?? 'Aucun compte auteur actif.'), ENT_QUOTES, 'UTF-8') ?></div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table align-middle mb-0">
          <thead>
            <tr>
              <th><?= htmlspecialchars((string) ($tr['account'] ?? 'Compte'), ENT_QUOTES, 'UTF-8') ?></th>
              <th><?= htmlspecialchars((string) ($tr['identity'] ?? 'Identite editoriale'), ENT_QUOTES, 'UTF-8') ?></th>
              <th><?= htmlspecialchars((string) ($tr['slug'] ?? 'Slug'), ENT_QUOTES, 'UTF-8') ?></th>
              <th><?= htmlspecialchars((string) ($tr['visibility'] ?? 'Visibilite'), ENT_QUOTES, 'UTF-8') ?></th>
              <th><?= htmlspecialchars((string) ($tr['socials'] ?? 'Reseaux sociaux'), ENT_QUOTES, 'UTF-8') ?></th>
              <th class="text-end"><?= htmlspecialchars((string) ($tr['actions'] ?? 'Actions'), ENT_QUOTES, 'UTF-8') ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($profiles as $profile): ?>
              <?php
                $profileId = (int) ($profile['id'] ?? 0);
                $userId = (int) ($profile['user_id'] ?? 0);
                $fullName = trim((string) ($profile['first_name'] ?? '') . ' ' . (string) ($profile['last_name'] ?? ''));
                $socials = $normalizeSocials($profile['socials_json'] ?? null);
                $visibilityLabel = $visibilities[(string) ($profile['visibility'] ?? 'public')] ?? (string) ($profile['visibility'] ?? 'public');
                $socialJson = htmlspecialchars((string) json_encode($socials, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
              ?>
              <tr>
                <td>
                  <div class="fw-semibold"><?= htmlspecialchars((string) ($profile['username'] ?? ('#' . $userId)), ENT_QUOTES, 'UTF-8') ?></div>
                  <?php if (!empty($profile['email'])): ?><div class="small text-body-secondary"><?= htmlspecialchars((string) $profile['email'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                </td>
                <td>
                  <div class="fw-semibold"><?= htmlspecialchars((string) ($profile['display_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                  <?php if ($fullName !== ''): ?><div class="small text-body-secondary"><?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                </td>
                <td><code><?= htmlspecialchars((string) ($profile['slug'] ?? ''), ENT_QUOTES, 'UTF-8') ?></code></td>
                <td><span class="badge text-bg-light"><?= htmlspecialchars((string) $visibilityLabel, ENT_QUOTES, 'UTF-8') ?></span></td>
                <td><span class="small text-body-secondary"><?= count($socials) ?></span></td>
                <td class="text-end">
                  <div class="btn-group btn-group-sm" role="group">
                    <button class="btn btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#editAuthorModal"
                      data-id="<?= $profileId ?>"
                      data-user-id="<?= $userId ?>"
                      data-first-name="<?= htmlspecialchars((string) ($profile['first_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                      data-last-name="<?= htmlspecialchars((string) ($profile['last_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                      data-display-name="<?= htmlspecialchars((string) ($profile['display_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                      data-slug="<?= htmlspecialchars((string) ($profile['slug'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                      data-visibility="<?= htmlspecialchars((string) ($profile['visibility'] ?? 'public'), ENT_QUOTES, 'UTF-8') ?>"
                      data-bio="<?= htmlspecialchars((string) ($profile['bio'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                      data-socials="<?= $socialJson ?>">
                      <i class="bi bi-pencil-square"></i>
                    </button>
                    <form method="post" action="<?= htmlspecialchars($adminBase . '/modules/author-bridge/profile/delete', ENT_QUOTES, 'UTF-8') ?>" onsubmit="return confirm('<?= htmlspecialchars((string) ($tr['confirm_delete'] ?? 'Retirer cette fiche auteur ?'), ENT_QUOTES, 'UTF-8') ?>');">
                      <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                      <input type="hidden" name="id" value="<?= $profileId ?>">
                      <button class="btn btn-outline-danger" type="submit"><i class="bi bi-trash"></i></button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<div class="modal fade" id="createAuthorModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <form method="post" action="<?= htmlspecialchars($adminBase . '/modules/author-bridge/profile/create', ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="_csrf" value="<?= $csrf ?>">
        <div class="modal-header">
          <h2 class="modal-title fs-5"><?= htmlspecialchars((string) ($tr['create_profile'] ?? 'Activer un compte auteur'), ENT_QUOTES, 'UTF-8') ?></h2>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label"><?= htmlspecialchars((string) ($tr['linked_user'] ?? 'Compte admin'), ENT_QUOTES, 'UTF-8') ?> <span class="text-danger">*</span></label>
              <select class="form-select" name="user_id" required>
                <option value=""><?= htmlspecialchars((string) ($tr['select_user'] ?? 'Selectionnez un compte'), ENT_QUOTES, 'UTF-8') ?></option>
                <?php foreach ($availableUsers as $user): ?>
                  <option value="<?= (int) ($user['id'] ?? 0) ?>"><?= htmlspecialchars((string) ($user['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12 col-md-6"><label class="form-label"><?= htmlspecialchars((string) ($tr['first_name'] ?? 'Prenom'), ENT_QUOTES, 'UTF-8') ?> *</label><input class="form-control" type="text" name="first_name" id="createFirstName" maxlength="120" required></div>
            <div class="col-12 col-md-6"><label class="form-label"><?= htmlspecialchars((string) ($tr['last_name'] ?? 'Nom'), ENT_QUOTES, 'UTF-8') ?> *</label><input class="form-control" type="text" name="last_name" id="createLastName" maxlength="120" required></div>
            <div class="col-12 col-md-6"><label class="form-label"><?= htmlspecialchars((string) ($tr['display_name'] ?? 'Nom d affichage'), ENT_QUOTES, 'UTF-8') ?> *</label><input class="form-control" type="text" name="display_name" id="createDisplayName" maxlength="160" required></div>
            <div class="col-12 col-md-6"><label class="form-label"><?= htmlspecialchars((string) ($tr['slug'] ?? 'Slug'), ENT_QUOTES, 'UTF-8') ?> *</label><input class="form-control" type="text" name="slug" id="createSlug" maxlength="200"></div>
            <div class="col-12 col-md-6">
              <label class="form-label"><?= htmlspecialchars((string) ($tr['visibility'] ?? 'Visibilite'), ENT_QUOTES, 'UTF-8') ?></label>
              <select class="form-select" name="visibility"><?php foreach ($visibilities as $key => $label): ?><option value="<?= htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select>
            </div>
            <div class="col-12"><label class="form-label"><?= htmlspecialchars((string) ($tr['bio'] ?? 'Biographie'), ENT_QUOTES, 'UTF-8') ?></label><textarea class="form-control" name="bio" rows="4"></textarea></div>
            <div class="col-12">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <label class="form-label mb-0"><?= htmlspecialchars((string) ($tr['socials'] ?? 'Reseaux sociaux'), ENT_QUOTES, 'UTF-8') ?></label>
                <button class="btn btn-sm btn-outline-secondary" type="button" data-social-add="createSocials"><i class="bi bi-plus-lg me-1"></i><?= htmlspecialchars((string) ($tr['btn_add_social'] ?? 'Ajouter un reseau'), ENT_QUOTES, 'UTF-8') ?></button>
              </div>
              <div data-social-container id="createSocials"></div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal"><?= htmlspecialchars((string) ($tr['btn_cancel'] ?? 'Annuler'), ENT_QUOTES, 'UTF-8') ?></button>
          <button class="btn btn-primary" type="submit"><?= htmlspecialchars((string) ($tr['btn_create'] ?? 'Activer ce compte auteur'), ENT_QUOTES, 'UTF-8') ?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="editAuthorModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <form method="post" action="<?= htmlspecialchars($adminBase . '/modules/author-bridge/profile/update', ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="_csrf" value="<?= $csrf ?>">
        <input type="hidden" name="id" id="editId" value="">
        <div class="modal-header">
          <h2 class="modal-title fs-5"><?= htmlspecialchars((string) ($tr['edit_profile'] ?? 'Modifier le compte auteur'), ENT_QUOTES, 'UTF-8') ?></h2>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label"><?= htmlspecialchars((string) ($tr['linked_user'] ?? 'Compte admin'), ENT_QUOTES, 'UTF-8') ?> *</label>
              <select class="form-select" name="user_id" id="editUserId" required><?php foreach ($users as $user): ?><option value="<?= (int) ($user['id'] ?? 0) ?>"><?= htmlspecialchars((string) ($user['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select>
            </div>
            <div class="col-12 col-md-6"><label class="form-label"><?= htmlspecialchars((string) ($tr['first_name'] ?? 'Prenom'), ENT_QUOTES, 'UTF-8') ?> *</label><input class="form-control" type="text" name="first_name" id="editFirstName" maxlength="120" required></div>
            <div class="col-12 col-md-6"><label class="form-label"><?= htmlspecialchars((string) ($tr['last_name'] ?? 'Nom'), ENT_QUOTES, 'UTF-8') ?> *</label><input class="form-control" type="text" name="last_name" id="editLastName" maxlength="120" required></div>
            <div class="col-12 col-md-6"><label class="form-label"><?= htmlspecialchars((string) ($tr['display_name'] ?? 'Nom d affichage'), ENT_QUOTES, 'UTF-8') ?> *</label><input class="form-control" type="text" name="display_name" id="editDisplayName" maxlength="160" required></div>
            <div class="col-12 col-md-6"><label class="form-label"><?= htmlspecialchars((string) ($tr['slug'] ?? 'Slug'), ENT_QUOTES, 'UTF-8') ?> *</label><input class="form-control" type="text" name="slug" id="editSlug" maxlength="200"></div>
            <div class="col-12 col-md-6"><label class="form-label"><?= htmlspecialchars((string) ($tr['visibility'] ?? 'Visibilite'), ENT_QUOTES, 'UTF-8') ?></label><select class="form-select" name="visibility" id="editVisibility"><?php foreach ($visibilities as $key => $label): ?><option value="<?= htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
            <div class="col-12"><label class="form-label"><?= htmlspecialchars((string) ($tr['bio'] ?? 'Biographie'), ENT_QUOTES, 'UTF-8') ?></label><textarea class="form-control" name="bio" id="editBio" rows="4"></textarea></div>
            <div class="col-12">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <label class="form-label mb-0"><?= htmlspecialchars((string) ($tr['socials'] ?? 'Reseaux sociaux'), ENT_QUOTES, 'UTF-8') ?></label>
                <button class="btn btn-sm btn-outline-secondary" type="button" data-social-add="editSocials"><i class="bi bi-plus-lg me-1"></i><?= htmlspecialchars((string) ($tr['btn_add_social'] ?? 'Ajouter un reseau'), ENT_QUOTES, 'UTF-8') ?></button>
              </div>
              <div data-social-container id="editSocials"></div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal"><?= htmlspecialchars((string) ($tr['btn_cancel'] ?? 'Annuler'), ENT_QUOTES, 'UTF-8') ?></button>
          <button class="btn btn-primary" type="submit"><?= htmlspecialchars((string) ($tr['btn_save'] ?? 'Enregistrer'), ENT_QUOTES, 'UTF-8') ?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php
$content = (string) ob_get_clean();

$inlineScripts = <<<'JS'
<script>
(function () {
  'use strict';

  const NETWORKS = {
    twitter: { label: 'X / Twitter', icon: 'twitter-x' },
    linkedin: { label: 'LinkedIn', icon: 'linkedin' },
    github: { label: 'GitHub', icon: 'github' },
    instagram: { label: 'Instagram', icon: 'instagram' },
    mastodon: { label: 'Mastodon', icon: 'mastodon' },
    facebook: { label: 'Facebook', icon: 'facebook' },
    youtube: { label: 'YouTube', icon: 'youtube' },
    tiktok: { label: 'TikTok', icon: 'music-note-beamed' },
    telegram: { label: 'Telegram', icon: 'send' },
    threads: { label: 'Threads', icon: 'at' },
    bluesky: { label: 'Bluesky', icon: 'cloud' }
  };

  function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>\"]/g, function (char) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[char];
    });
  }

  function networkOptions(selected) {
    return Object.entries(NETWORKS).map(function (entry) {
      const value = entry[0];
      const meta = entry[1];
      const isSelected = value === selected ? ' selected' : '';
      return '<option value="' + escapeHtml(value) + '"' + isSelected + '>' + escapeHtml(meta.label) + '</option>';
    }).join('');
  }

  function rowMarkup(item) {
    const network = item && item.network ? item.network : 'twitter';
    const url = item && item.url ? item.url : '';
    const icon = (NETWORKS[network] && NETWORKS[network].icon) || 'link-45deg';
    return '<div class="input-group mb-2" data-social-row>' +
      '<span class="input-group-text"><i class="bi bi-' + escapeHtml(icon) + '" data-social-icon></i></span>' +
      '<select class="form-select" name="social_network[]" data-social-network>' + networkOptions(network) + '</select>' +
      '<input class="form-control" type="url" name="social_url[]" placeholder="https://..." data-social-url value="' + escapeHtml(url) + '">' +
      '<button class="btn btn-outline-danger" type="button" data-social-remove><i class="bi bi-dash-lg"></i></button>' +
    '</div>';
  }

  function syncRowIcon(row) {
    const select = row.querySelector('[data-social-network]');
    const icon = row.querySelector('[data-social-icon]');
    if (!select || !icon) {
      return;
    }
    const value = select.value;
    const iconName = (NETWORKS[value] && NETWORKS[value].icon) || 'link-45deg';
    icon.className = 'bi bi-' + iconName;
  }

  function setRows(container, items) {
    if (!container) {
      return;
    }
    container.innerHTML = '';
    const normalized = Array.isArray(items) && items.length > 0 ? items : [{}];
    normalized.forEach(function (item) {
      container.insertAdjacentHTML('beforeend', rowMarkup(item));
    });
    container.querySelectorAll('[data-social-row]').forEach(syncRowIcon);
  }

  function ensureRows(container) {
    if (!container) {
      return;
    }
    if (container.querySelectorAll('[data-social-row]').length === 0) {
      container.insertAdjacentHTML('beforeend', rowMarkup({}));
    }
  }

  document.querySelectorAll('[data-social-add]').forEach(function (button) {
    button.addEventListener('click', function () {
      const target = document.getElementById(button.getAttribute('data-social-add'));
      if (!target) {
        return;
      }
      target.insertAdjacentHTML('beforeend', rowMarkup({}));
      syncRowIcon(target.lastElementChild);
    });
  });

  document.addEventListener('change', function (event) {
    const select = event.target.closest('[data-social-network]');
    if (select) {
      syncRowIcon(select.closest('[data-social-row]'));
    }
  });

  document.addEventListener('click', function (event) {
    const removeButton = event.target.closest('[data-social-remove]');
    if (!removeButton) {
      return;
    }
    const row = removeButton.closest('[data-social-row]');
    const container = row ? row.parentElement : null;
    if (row) {
      row.remove();
    }
    ensureRows(container);
    if (container) {
      container.querySelectorAll('[data-social-row]').forEach(syncRowIcon);
    }
  });

  function slugify(value) {
    return String(value || '')
      .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
      .toLowerCase()
      .replace(/[^a-z0-9-]+/g, '-')
      .replace(/-{2,}/g, '-')
      .replace(/^-|-$/g, '');
  }

  function wireSlug(sourceId, targetId) {
    const source = document.getElementById(sourceId);
    const target = document.getElementById(targetId);
    if (!source || !target) {
      return;
    }
    source.addEventListener('input', function () {
      if (target.dataset.locked === '1') {
        return;
      }
      target.value = slugify(source.value);
    });
    target.addEventListener('input', function () {
      target.dataset.locked = target.value.trim() === '' ? '0' : '1';
    });
  }

  const createContainer = document.getElementById('createSocials');
  const editContainer = document.getElementById('editSocials');
  setRows(createContainer, [{}]);

  wireSlug('createDisplayName', 'createSlug');
  wireSlug('editDisplayName', 'editSlug');

  const editModal = document.getElementById('editAuthorModal');
  if (editModal) {
    editModal.addEventListener('show.bs.modal', function (event) {
      const button = event.relatedTarget;
      if (!button) {
        return;
      }

      document.getElementById('editId').value = button.getAttribute('data-id') || '';
      document.getElementById('editUserId').value = button.getAttribute('data-user-id') || '';
      document.getElementById('editFirstName').value = button.getAttribute('data-first-name') || '';
      document.getElementById('editLastName').value = button.getAttribute('data-last-name') || '';
      document.getElementById('editDisplayName').value = button.getAttribute('data-display-name') || '';
      document.getElementById('editSlug').value = button.getAttribute('data-slug') || '';
      document.getElementById('editSlug').dataset.locked = (document.getElementById('editSlug').value.trim() === '' ? '0' : '1');
      document.getElementById('editVisibility').value = button.getAttribute('data-visibility') || 'public';
      document.getElementById('editBio').value = button.getAttribute('data-bio') || '';

      let socials = [];
      try {
        socials = JSON.parse(button.getAttribute('data-socials') || '[]');
      } catch (error) {
        socials = [];
      }
      setRows(editContainer, Array.isArray(socials) && socials.length > 0 ? socials : [{}]);
    });
  }
}());
</script>
JS;

require CATMIN_ADMIN . '/views/layouts/admin.php';
