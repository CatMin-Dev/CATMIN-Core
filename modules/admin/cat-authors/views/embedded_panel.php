<?php

declare(strict_types=1);

use Core\security\CsrfManager;

$profiles   = isset($profiles) && is_array($profiles) ? $profiles : [];
$selectedId = isset($selectedId) ? ((int) $selectedId ?: null) : null;
$entityType = isset($entityType) ? strtolower(trim((string) $entityType)) : '';
$entityId   = isset($entityId) ? (int) $entityId : 0;
$tr         = isset($tr) && is_array($tr) ? $tr : [];
$csrf       = htmlspecialchars((new CsrfManager())->token(), ENT_QUOTES, 'UTF-8');

?>
<div class="cat-author-panel">
  <label class="form-label fw-semibold">
    <i class="bi bi-person-badge me-1"></i>
    <?= htmlspecialchars((string) ($tr['panel_label'] ?? 'Auteur'), ENT_QUOTES, 'UTF-8') ?>
  </label>

  <?php if ($profiles === []): ?>
    <p class="text-body-secondary small mb-0">
      <?= htmlspecialchars((string) ($tr['no_profiles_panel'] ?? 'Aucun profil auteur disponible.'), ENT_QUOTES, 'UTF-8') ?>
    </p>
  <?php else: ?>
    <select class="form-select" name="author_profile_id" id="authorPanelSelect">
      <option value="">— <?= htmlspecialchars((string) ($tr['no_author'] ?? 'Sans auteur'), ENT_QUOTES, 'UTF-8') ?> —</option>
      <?php foreach ($profiles as $p): ?>
        <?php $pid = (int) ($p['id'] ?? 0); ?>
        <option value="<?= $pid ?>"
                <?= $pid === $selectedId ? 'selected' : '' ?>
                data-username="<?= htmlspecialchars((string) ($p['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
          <?= htmlspecialchars((string) ($p['display_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
          <?php if (!empty($p['username'])): ?>
            <span class="text-body-secondary">(<?= htmlspecialchars((string) $p['username'], ENT_QUOTES, 'UTF-8') ?>)</span>
          <?php endif; ?>
        </option>
      <?php endforeach; ?>
    </select>

    <!-- Author preview badge -->
    <div id="authorPanelPreview" class="mt-2">
      <?php
        $selProfile = null;
        if ($selectedId !== null) {
            foreach ($profiles as $p) {
                if ((int) ($p['id'] ?? 0) === $selectedId) {
                    $selProfile = $p;
                    break;
                }
            }
        }
      ?>
      <?php if ($selProfile !== null): ?>
        <span class="badge text-bg-info">
          <i class="bi bi-person-check me-1"></i>
          <?= htmlspecialchars((string) ($selProfile['display_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
        </span>
      <?php endif; ?>
    </div>

    <div class="form-text"><?= htmlspecialchars((string) ($tr['panel_help'] ?? 'Vous pouvez changer l\'auteur depuis la section Organisation → Auteurs.'), ENT_QUOTES, 'UTF-8') ?></div>
  <?php endif; ?>
</div>

<script>
(function () {
  'use strict';
  const sel = document.getElementById('authorPanelSelect');
  const preview = document.getElementById('authorPanelPreview');
  if (!sel || !preview) return;
  sel.addEventListener('change', function () {
    const opt = sel.options[sel.selectedIndex];
    if (sel.value === '') {
      preview.innerHTML = '';
      return;
    }
    const name = opt.textContent.trim().split('(')[0].trim();
    preview.innerHTML = '<span class="badge text-bg-info"><i class="bi bi-person-check me-1"></i>' +
      name.replace(/[<>&"]/g, c => ({
        '<': '&lt;', '>': '&gt;', '&': '&amp;', '"': '&quot;'
      }[c])) + '</span>';
  });
}());
</script>
