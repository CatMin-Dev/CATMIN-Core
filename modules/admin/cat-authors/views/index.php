<?php

declare(strict_types=1);

use Core\security\CsrfManager;

$tab          = isset($tab) && is_string($tab) ? $tab : 'profiles';
$dashboard    = isset($dashboard) && is_array($dashboard) ? $dashboard : ['total' => 0, 'profiles' => [], 'users' => []];
$rolesWithFlag= isset($rolesWithFlag) && is_array($rolesWithFlag) ? $rolesWithFlag : [];
$profiles     = isset($dashboard['profiles']) && is_array($dashboard['profiles']) ? $dashboard['profiles'] : [];
$users        = isset($dashboard['users']) && is_array($dashboard['users']) ? $dashboard['users'] : [];
$total        = (int) ($dashboard['total'] ?? 0);
$message      = isset($message) ? trim((string) $message) : '';
$messageType  = isset($messageType) ? trim((string) $messageType) : 'info';
$tr           = isset($tr) && is_array($tr) ? $tr : [];
$adminBase    = isset($adminBase) ? (string) $adminBase : '/admin';
$csrf         = htmlspecialchars((new CsrfManager())->token(), ENT_QUOTES, 'UTF-8');

$pageTitle       = (string) ($tr['title'] ?? 'Auteurs');
$pageDescription = (string) ($tr['description'] ?? 'Gestion des profils auteurs');
$activeNav       = 'author-bridge';
$breadcrumbs     = [
    ['label' => __('common.admin'), 'href' => $adminBase . '/'],
    ['label' => 'Organisation'],
    ['label' => $pageTitle],
];

// Visibility options
$visibilities = [
    'public'   => ($tr['visibility_public']   ?? 'Public'),
    'private'  => ($tr['visibility_private']  ?? 'Privé'),
    'unlisted' => ($tr['visibility_unlisted'] ?? 'Non listé'),
];

ob_start();
?>

<?php if ($message !== ''): ?>
<div class="alert alert-<?= htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8') ?> mb-3">
  <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
</div>
<?php endif; ?>

<!-- Stat cards -->
<div class="row g-3 mb-3">
  <div class="col-12 col-md-4">
    <div class="card h-100"><div class="card-body">
      <p class="small text-body-secondary mb-1"><?= htmlspecialchars((string) ($tr['total_profiles'] ?? 'Profils auteurs'), ENT_QUOTES, 'UTF-8') ?></p>
      <p class="h3 mb-0"><?= $total ?></p>
    </div></div>
  </div>
  <div class="col-12 col-md-4">
    <div class="card h-100"><div class="card-body">
      <p class="small text-body-secondary mb-1"><?= htmlspecialchars((string) ($tr['total_users'] ?? 'Comptes admin disponibles'), ENT_QUOTES, 'UTF-8') ?></p>
      <p class="h3 mb-0"><?= count($users) ?></p>
    </div></div>
  </div>
  <div class="col-12 col-md-4">
    <div class="card h-100"><div class="card-body">
      <p class="small text-body-secondary mb-1"><?= htmlspecialchars((string) ($tr['registered_roles'] ?? 'Rôles auteurs signalés'), ENT_QUOTES, 'UTF-8') ?></p>
      <p class="h3 mb-0"><?= count(array_filter($rolesWithFlag, static fn ($r) => (bool) ($r['is_author_role'] ?? false))) ?></p>
    </div></div>
  </div>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-3" id="authorTabs">
  <li class="nav-item">
    <a class="nav-link <?= $tab === 'profiles' ? 'active' : '' ?>"
       href="<?= htmlspecialchars($adminBase . '/modules/author-bridge?tab=profiles', ENT_QUOTES, 'UTF-8') ?>">
      <i class="bi bi-person-lines-fill me-1"></i>
      <?= htmlspecialchars((string) ($tr['tab_profiles'] ?? 'Profils auteurs'), ENT_QUOTES, 'UTF-8') ?>
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?= $tab === 'roles' ? 'active' : '' ?>"
       href="<?= htmlspecialchars($adminBase . '/modules/author-bridge?tab=roles', ENT_QUOTES, 'UTF-8') ?>">
      <i class="bi bi-shield-check me-1"></i>
      <?= htmlspecialchars((string) ($tr['tab_roles'] ?? 'Rôles autorisés'), ENT_QUOTES, 'UTF-8') ?>
    </a>
  </li>
</ul>

<?php if ($tab === 'profiles'): ?>
<!-- ================================================================ PROFILES TAB -->

  <!-- Create profile form -->
  <div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span class="fw-semibold"><i class="bi bi-person-plus me-1"></i><?= htmlspecialchars((string) ($tr['create_profile'] ?? 'Créer un profil auteur'), ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <div class="card-body">
      <form method="post" action="<?= htmlspecialchars($adminBase . '/modules/author-bridge/profile/create', ENT_QUOTES, 'UTF-8') ?>" class="row g-2">
        <input type="hidden" name="_csrf" value="<?= $csrf ?>">

        <!-- Link to admin user -->
        <div class="col-12 col-md-6">
          <label class="form-label"><?= htmlspecialchars((string) ($tr['linked_user'] ?? 'Compte admin lié (optionnel)'), ENT_QUOTES, 'UTF-8') ?></label>
          <select class="form-select" name="user_id">
            <option value="">— <?= htmlspecialchars((string) ($tr['no_user'] ?? 'Sans compte lié'), ENT_QUOTES, 'UTF-8') ?> —</option>
            <?php foreach ($users as $u): ?>
              <?php $uid = (int) ($u['id'] ?? 0); $hasPro = (bool) ($u['has_profile'] ?? false); ?>
              <option value="<?= $uid ?>" <?= $hasPro ? 'disabled' : '' ?>>
                <?= htmlspecialchars((string) ($u['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                (<?= htmlspecialchars((string) ($u['role_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>)
                <?= $hasPro ? ' — ' . htmlspecialchars((string) ($tr['already_linked'] ?? 'déjà lié'), ENT_QUOTES, 'UTF-8') : '' ?>
              </option>
            <?php endforeach; ?>
          </select>
          <div class="form-text"><?= htmlspecialchars((string) ($tr['linked_user_help'] ?? 'Un compte admin par profil. Un profil peut exister sans compte lié.'), ENT_QUOTES, 'UTF-8') ?></div>
        </div>

        <div class="col-12 col-md-6">
          <label class="form-label"><?= htmlspecialchars((string) ($tr['display_name'] ?? 'Nom d\'affichage'), ENT_QUOTES, 'UTF-8') ?> <span class="text-danger">*</span></label>
          <input class="form-control" name="display_name" required maxlength="160" placeholder="Ex: Jean Dupont">
        </div>
        <div class="col-12 col-md-4">
          <label class="form-label"><?= htmlspecialchars((string) ($tr['slug'] ?? 'Slug (auto-généré si vide)'), ENT_QUOTES, 'UTF-8') ?></label>
          <input class="form-control" name="slug" pattern="[a-z0-9\-]+" placeholder="jean-dupont" id="slugInput">
        </div>
        <div class="col-12 col-md-4">
          <label class="form-label"><?= htmlspecialchars((string) ($tr['visibility'] ?? 'Visibilité'), ENT_QUOTES, 'UTF-8') ?></label>
          <select class="form-select" name="visibility">
            <?php foreach ($visibilities as $vKey => $vLabel): ?>
              <option value="<?= htmlspecialchars($vKey, ENT_QUOTES, 'UTF-8') ?>" <?= $vKey === 'public' ? 'selected' : '' ?>>
                <?= htmlspecialchars($vLabel, ENT_QUOTES, 'UTF-8') ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-12 col-md-4">
          <label class="form-label"><?= htmlspecialchars((string) ($tr['website_url'] ?? 'Site web'), ENT_QUOTES, 'UTF-8') ?></label>
          <input class="form-control" name="website_url" type="url" placeholder="https://exemple.com">
        </div>
        <div class="col-12">
          <label class="form-label"><?= htmlspecialchars((string) ($tr['bio'] ?? 'Biographie'), ENT_QUOTES, 'UTF-8') ?></label>
          <textarea class="form-control" name="bio" rows="3" placeholder="..."></textarea>
        </div>

        <!-- Socials -->
        <div class="col-12">
          <p class="small text-body-secondary mb-1"><?= htmlspecialchars((string) ($tr['socials'] ?? 'Réseaux sociaux'), ENT_QUOTES, 'UTF-8') ?></p>
          <div class="row g-2">
            <?php foreach (['twitter' => 'Twitter/X', 'linkedin' => 'LinkedIn', 'github' => 'GitHub', 'instagram' => 'Instagram', 'mastodon' => 'Mastodon'] as $sKey => $sLabel): ?>
              <div class="col-12 col-md-4 col-lg-2">
                <input class="form-control form-control-sm" name="social_<?= $sKey ?>" placeholder="<?= htmlspecialchars($sLabel, ENT_QUOTES, 'UTF-8') ?>">
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="col-12 d-flex justify-content-end">
          <button class="btn btn-primary" type="submit">
            <i class="bi bi-person-plus me-1"></i><?= htmlspecialchars((string) ($tr['btn_create'] ?? 'Créer le profil'), ENT_QUOTES, 'UTF-8') ?>
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Profile listing -->
  <div class="card">
    <div class="card-header fw-semibold">
      <i class="bi bi-people me-1"></i><?= htmlspecialchars((string) ($tr['listing_title'] ?? 'Profils auteurs'), ENT_QUOTES, 'UTF-8') ?>
      <span class="badge text-bg-secondary ms-2"><?= $total ?></span>
    </div>
    <div class="card-body p-0">
      <?php if ($profiles === []): ?>
        <p class="p-3 text-body-secondary mb-0"><?= htmlspecialchars((string) ($tr['no_profiles'] ?? 'Aucun profil auteur.'), ENT_QUOTES, 'UTF-8') ?></p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th><?= htmlspecialchars((string) ($tr['display_name'] ?? 'Nom'), ENT_QUOTES, 'UTF-8') ?></th>
                <th><?= htmlspecialchars((string) ($tr['slug'] ?? 'Slug'), ENT_QUOTES, 'UTF-8') ?></th>
                <th><?= htmlspecialchars((string) ($tr['linked_user'] ?? 'Compte lié'), ENT_QUOTES, 'UTF-8') ?></th>
                <th><?= htmlspecialchars((string) ($tr['visibility'] ?? 'Visibilité'), ENT_QUOTES, 'UTF-8') ?></th>
                <th class="text-end"><?= htmlspecialchars((string) ($tr['actions'] ?? 'Actions'), ENT_QUOTES, 'UTF-8') ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($profiles as $p): ?>
                <?php
                  $pid     = (int) ($p['id'] ?? 0);
                  $pName   = htmlspecialchars((string) ($p['display_name'] ?? ''), ENT_QUOTES, 'UTF-8');
                  $pSlug   = htmlspecialchars((string) ($p['slug'] ?? ''), ENT_QUOTES, 'UTF-8');
                  $pUser   = htmlspecialchars((string) ($p['username'] ?? ''), ENT_QUOTES, 'UTF-8');
                  $pVis    = (string) ($p['visibility'] ?? 'public');
                  $visBadge= match ($pVis) {
                      'private' => 'text-bg-danger',
                      'unlisted'=> 'text-bg-warning',
                      default   => 'text-bg-success',
                  };
                  $socialsRaw = $p['socials_json'] ?? null;
                  $socials    = is_string($socialsRaw) && $socialsRaw !== '' ? (json_decode($socialsRaw, true) ?: []) : [];
                ?>
                <tr>
                  <td>
                    <strong><?= $pName ?></strong>
                    <?php if (!empty($p['bio'])): ?>
                      <br><small class="text-body-secondary"><?= htmlspecialchars(mb_substr((string) $p['bio'], 0, 60, 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>…</small>
                    <?php endif; ?>
                  </td>
                  <td><code class="small"><?= $pSlug ?></code></td>
                  <td><?= $pUser !== '' ? '<i class="bi bi-person-check text-success me-1"></i>' . $pUser : '<span class="text-body-secondary small">—</span>' ?></td>
                  <td><span class="badge <?= $visBadge ?>"><?= htmlspecialchars($visibilities[$pVis] ?? $pVis, ENT_QUOTES, 'UTF-8') ?></span></td>
                  <td class="text-end">
                    <!-- Edit button triggers modal -->
                    <button class="btn btn-sm btn-outline-secondary"
                            data-bs-toggle="modal" data-bs-target="#editModal"
                            data-pid="<?= $pid ?>"
                            data-display-name="<?= $pName ?>"
                            data-slug="<?= $pSlug ?>"
                            data-user-id="<?= (int) ($p['user_id'] ?? 0) ?>"
                            data-visibility="<?= htmlspecialchars($pVis, ENT_QUOTES, 'UTF-8') ?>"
                            data-bio="<?= htmlspecialchars((string) ($p['bio'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                            data-website="<?= htmlspecialchars((string) ($p['website_url'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                            data-socials="<?= htmlspecialchars((string) ($p['socials_json'] ?? '{}'), ENT_QUOTES, 'UTF-8') ?>">
                      <i class="bi bi-pencil"></i>
                    </button>
                    <!-- Delete -->
                    <form method="post" class="d-inline"
                          action="<?= htmlspecialchars($adminBase . '/modules/author-bridge/profile/delete', ENT_QUOTES, 'UTF-8') ?>"
                          onsubmit="return confirm('<?= htmlspecialchars((string) ($tr['confirm_delete'] ?? 'Supprimer ce profil ?'), ENT_QUOTES, 'UTF-8') ?>')">
                      <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                      <input type="hidden" name="id" value="<?= $pid ?>">
                      <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-trash"></i></button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Edit Modal -->
  <div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <form method="post" action="<?= htmlspecialchars($adminBase . '/modules/author-bridge/profile/update', ENT_QUOTES, 'UTF-8') ?>">
          <input type="hidden" name="_csrf" value="<?= $csrf ?>">
          <input type="hidden" name="id" id="editId">
          <div class="modal-header">
            <h5 class="modal-title"><?= htmlspecialchars((string) ($tr['edit_profile'] ?? 'Modifier le profil'), ENT_QUOTES, 'UTF-8') ?></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="row g-2">
              <div class="col-12 col-md-6">
                <label class="form-label"><?= htmlspecialchars((string) ($tr['linked_user'] ?? 'Compte admin lié'), ENT_QUOTES, 'UTF-8') ?></label>
                <select class="form-select" name="user_id" id="editUserId">
                  <option value="">— <?= htmlspecialchars((string) ($tr['no_user'] ?? 'Sans compte'), ENT_QUOTES, 'UTF-8') ?> —</option>
                  <?php foreach ($users as $u): ?>
                    <option value="<?= (int) ($u['id'] ?? 0) ?>">
                      <?= htmlspecialchars((string) ($u['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                      (<?= htmlspecialchars((string) ($u['role_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>)
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-12 col-md-6">
                <label class="form-label"><?= htmlspecialchars((string) ($tr['display_name'] ?? 'Nom d\'affichage'), ENT_QUOTES, 'UTF-8') ?> <span class="text-danger">*</span></label>
                <input class="form-control" name="display_name" id="editName" required maxlength="160">
              </div>
              <div class="col-12 col-md-4">
                <label class="form-label"><?= htmlspecialchars((string) ($tr['slug'] ?? 'Slug'), ENT_QUOTES, 'UTF-8') ?></label>
                <input class="form-control" name="slug" id="editSlug" pattern="[a-z0-9\-]+">
              </div>
              <div class="col-12 col-md-4">
                <label class="form-label"><?= htmlspecialchars((string) ($tr['visibility'] ?? 'Visibilité'), ENT_QUOTES, 'UTF-8') ?></label>
                <select class="form-select" name="visibility" id="editVisibility">
                  <?php foreach ($visibilities as $vKey => $vLabel): ?>
                    <option value="<?= htmlspecialchars($vKey, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($vLabel, ENT_QUOTES, 'UTF-8') ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-12 col-md-4">
                <label class="form-label"><?= htmlspecialchars((string) ($tr['website_url'] ?? 'Site web'), ENT_QUOTES, 'UTF-8') ?></label>
                <input class="form-control" name="website_url" id="editWebsite" type="url">
              </div>
              <div class="col-12">
                <label class="form-label"><?= htmlspecialchars((string) ($tr['bio'] ?? 'Biographie'), ENT_QUOTES, 'UTF-8') ?></label>
                <textarea class="form-control" name="bio" id="editBio" rows="3"></textarea>
              </div>
              <div class="col-12">
                <p class="small text-body-secondary mb-1"><?= htmlspecialchars((string) ($tr['socials'] ?? 'Réseaux sociaux'), ENT_QUOTES, 'UTF-8') ?></p>
                <div class="row g-2">
                  <?php foreach (['twitter' => 'Twitter/X', 'linkedin' => 'LinkedIn', 'github' => 'GitHub', 'instagram' => 'Instagram', 'mastodon' => 'Mastodon'] as $sKey => $sLabel): ?>
                    <div class="col-12 col-md-4 col-lg-2">
                      <input class="form-control form-control-sm" name="social_<?= $sKey ?>" id="editSocial_<?= $sKey ?>" placeholder="<?= htmlspecialchars($sLabel, ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= htmlspecialchars((string) ($tr['btn_cancel'] ?? 'Annuler'), ENT_QUOTES, 'UTF-8') ?></button>
            <button type="submit" class="btn btn-primary"><?= htmlspecialchars((string) ($tr['btn_save'] ?? 'Enregistrer'), ENT_QUOTES, 'UTF-8') ?></button>
          </div>
        </form>
      </div>
    </div>
  </div>

<?php elseif ($tab === 'roles'): ?>
<!-- ================================================================ ROLES TAB -->

  <div class="card">
    <div class="card-header">
      <i class="bi bi-shield-check me-1"></i>
      <strong><?= htmlspecialchars((string) ($tr['roles_title'] ?? 'Rôles auteurs autorisés'), ENT_QUOTES, 'UTF-8') ?></strong>
    </div>
    <div class="card-body">
      <div class="alert alert-info mb-3">
        <i class="bi bi-info-circle me-1"></i>
        <?= htmlspecialchars((string) ($tr['roles_info'] ?? 'Signalez ici les rôles admin qui correspondent à des auteurs. Aucune permission n\'est créée ni assignée automatiquement. Ce registre sert de référence uniquement.'), ENT_QUOTES, 'UTF-8') ?>
      </div>
      <form method="post" action="<?= htmlspecialchars($adminBase . '/modules/author-bridge/roles/save', ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="_csrf" value="<?= $csrf ?>">
        <?php if ($rolesWithFlag === []): ?>
          <p class="text-body-secondary"><?= htmlspecialchars((string) ($tr['no_roles'] ?? 'Aucun rôle trouvé dans le système.'), ENT_QUOTES, 'UTF-8') ?></p>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-bordered mb-3">
              <thead class="table-light">
                <tr>
                  <th style="width:40px"><?= htmlspecialchars((string) ($tr['col_author'] ?? 'Auteur'), ENT_QUOTES, 'UTF-8') ?></th>
                  <th><?= htmlspecialchars((string) ($tr['col_role_name'] ?? 'Rôle'), ENT_QUOTES, 'UTF-8') ?></th>
                  <th><?= htmlspecialchars((string) ($tr['col_role_slug'] ?? 'Slug'), ENT_QUOTES, 'UTF-8') ?></th>
                  <th><?= htmlspecialchars((string) ($tr['col_note'] ?? 'Note (optionnelle)'), ENT_QUOTES, 'UTF-8') ?></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($rolesWithFlag as $role): ?>
                  <?php
                    $rid        = (int) ($role['id'] ?? 0);
                    $rName      = htmlspecialchars((string) ($role['name'] ?? ''), ENT_QUOTES, 'UTF-8');
                    $rSlug      = htmlspecialchars((string) ($role['slug'] ?? ''), ENT_QUOTES, 'UTF-8');
                    $rNote      = htmlspecialchars((string) ($role['note'] ?? ''), ENT_QUOTES, 'UTF-8');
                    $rIsAuthor  = (bool) ($role['is_author_role'] ?? false);
                    $rIsSystem  = (bool) ($role['is_system'] ?? false);
                  ?>
                  <tr>
                    <td class="text-center">
                      <div class="form-check d-flex justify-content-center">
                        <input class="form-check-input" type="checkbox" name="role_ids[]"
                               value="<?= $rid ?>"
                               id="role_<?= $rid ?>"
                               <?= $rIsAuthor ? 'checked' : '' ?>>
                      </div>
                    </td>
                    <td>
                      <label for="role_<?= $rid ?>" class="mb-0">
                        <?= $rName ?>
                        <?php if ($rIsSystem): ?>
                          <span class="badge text-bg-secondary ms-1 small"><?= htmlspecialchars((string) ($tr['system_role'] ?? 'système'), ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                      </label>
                    </td>
                    <td><code class="small"><?= $rSlug ?></code></td>
                    <td>
                      <input class="form-control form-control-sm"
                             name="role_notes[<?= $rid ?>]"
                             value="<?= $rNote ?>"
                             placeholder="<?= htmlspecialchars((string) ($tr['note_placeholder'] ?? 'Ex: Rôle rédacteur blog'), ENT_QUOTES, 'UTF-8') ?>">
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <div class="d-flex justify-content-end">
            <button class="btn btn-primary" type="submit">
              <i class="bi bi-save me-1"></i>
              <?= htmlspecialchars((string) ($tr['btn_save_roles'] ?? 'Enregistrer le registre'), ENT_QUOTES, 'UTF-8') ?>
            </button>
          </div>
        <?php endif; ?>
      </form>
    </div>
  </div>

<?php endif; ?>

<?php
$content = (string) ob_get_clean();

$scripts = <<<'JS'
<script>
(function () {
  'use strict';

  // Auto-slug from display_name on create form
  const nameInput = document.querySelector('input[name="display_name"]');
  const slugInput = document.getElementById('slugInput');
  if (nameInput && slugInput) {
    nameInput.addEventListener('input', function () {
      if (slugInput.dataset.manual === '1') return;
      slugInput.value = nameInput.value
        .toLowerCase()
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
    });
    slugInput.addEventListener('input', function () {
      slugInput.dataset.manual = slugInput.value !== '' ? '1' : '0';
    });
  }

  // Populate edit modal
  const editModal = document.getElementById('editModal');
  if (editModal) {
    editModal.addEventListener('show.bs.modal', function (e) {
      const btn = e.relatedTarget;
      if (!btn) return;
      document.getElementById('editId').value          = btn.dataset.pid ?? '';
      document.getElementById('editName').value        = btn.dataset.displayName ?? '';
      document.getElementById('editSlug').value        = btn.dataset.slug ?? '';
      document.getElementById('editBio').value         = btn.dataset.bio ?? '';
      document.getElementById('editWebsite').value     = btn.dataset.website ?? '';
      const uid = btn.dataset.userId ?? '0';
      const userSel = document.getElementById('editUserId');
      if (userSel) {
        const opt = userSel.querySelector('option[value="' + uid + '"]');
        if (opt) opt.selected = true;
        else userSel.value = '';
      }
      const visSel = document.getElementById('editVisibility');
      if (visSel) {
        const vis = btn.dataset.visibility ?? 'public';
        const opt = visSel.querySelector('option[value="' + vis + '"]');
        if (opt) opt.selected = true;
      }
      // Populate socials
      let socials = {};
      try { socials = JSON.parse(btn.dataset.socials ?? '{}'); } catch(e) {}
      ['twitter','linkedin','github','instagram','mastodon'].forEach(function (k) {
        const inp = document.getElementById('editSocial_' + k);
        if (inp) inp.value = socials[k] ?? '';
      });
    });
  }
}());
</script>
JS;

require CATMIN_ADMIN . '/views/layouts/admin.php';
