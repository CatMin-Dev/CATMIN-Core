<?php

declare(strict_types=1);

use Core\security\CsrfManager;

$dashboard = isset($dashboard) && is_array($dashboard) ? $dashboard : ['stats' => [], 'tags' => []];
$stats = isset($dashboard['stats']) && is_array($dashboard['stats']) ? $dashboard['stats'] : [];
$rows = isset($dashboard['tags']) && is_array($dashboard['tags']) ? $dashboard['tags'] : [];
$tagsCsv = isset($tagsCsv) ? trim((string) $tagsCsv) : '';
$entityType = isset($entityType) ? strtolower(trim((string) $entityType)) : 'page';
$entityId = isset($entityId) ? (int) $entityId : 0;
$message = isset($message) ? trim((string) $message) : '';
$messageType = isset($messageType) ? trim((string) $messageType) : 'info';
$tr = isset($tr) && is_array($tr) ? $tr : [];
$adminBase = isset($adminBase) ? (string) $adminBase : '/admin';
$csrf = htmlspecialchars((new CsrfManager())->token(), ENT_QUOTES, 'UTF-8');

$pageTitle = (string) ($tr['title'] ?? 'Tags Bridge');
$pageDescription = (string) ($tr['description'] ?? 'Tags management');
$activeNav = 'cat-tags.dashboard';
$breadcrumbs = [
    ['label' => __('common.admin'), 'href' => $adminBase . '/'],
    ['label' => __('nav.modules')],
    ['label' => (string) ($tr['title'] ?? 'Tags Bridge')],
];

ob_start();
?>
<?php if ($message !== ''): ?><section class="alert alert-<?= htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8') ?> mb-3"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></section><?php endif; ?>

<section class="row g-3 mb-3">
  <div class="col-12 col-md-4"><div class="card h-100"><div class="card-body"><p class="small text-body-secondary mb-1"><?= htmlspecialchars((string) ($tr['total_tags'] ?? 'Total tags'), ENT_QUOTES, 'UTF-8') ?></p><p class="h3 mb-0"><?= (int) ($stats['total_tags'] ?? 0) ?></p></div></div></div>
  <div class="col-12 col-md-4"><div class="card h-100"><div class="card-body"><p class="small text-body-secondary mb-1"><?= htmlspecialchars((string) ($tr['total_links'] ?? 'Total links'), ENT_QUOTES, 'UTF-8') ?></p><p class="h3 mb-0"><?= (int) ($stats['total_links'] ?? 0) ?></p></div></div></div>
  <div class="col-12 col-md-4"><div class="card h-100"><div class="card-body"><p class="small text-body-secondary mb-1"><?= htmlspecialchars((string) ($tr['used_tags'] ?? 'Used tags'), ENT_QUOTES, 'UTF-8') ?></p><p class="h3 mb-0"><?= (int) ($stats['used_tags'] ?? 0) ?></p></div></div></div>
</section>

<section class="card mb-3"><div class="card-body">
  <h2 class="h5 mb-3">UI embarquee tags</h2>
  <form method="post" action="<?= htmlspecialchars($adminBase . '/modules/tags-bridge/sync', ENT_QUOTES, 'UTF-8') ?>" class="row g-2">
    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
    <div class="col-12 col-md-3"><label class="form-label"><?= htmlspecialchars((string) ($tr['entity_type'] ?? 'Entity type'), ENT_QUOTES, 'UTF-8') ?></label><input class="form-control" name="entity_type" value="<?= htmlspecialchars($entityType, ENT_QUOTES, 'UTF-8') ?>" required></div>
    <div class="col-12 col-md-2"><label class="form-label"><?= htmlspecialchars((string) ($tr['entity_id'] ?? 'Entity ID'), ENT_QUOTES, 'UTF-8') ?></label><input class="form-control" type="number" min="1" name="entity_id" value="<?= $entityId ?>" required></div>
    <div class="col-12 col-md-7">
      <label class="form-label"><?= htmlspecialchars((string) ($tr['tags_input'] ?? 'Tags'), ENT_QUOTES, 'UTF-8') ?></label>
      <input type="hidden" name="tags_csv" id="tags-csv" value="<?= htmlspecialchars($tagsCsv, ENT_QUOTES, 'UTF-8') ?>">
      <div class="border rounded p-2" id="tags-chip-box">
        <div class="d-flex flex-wrap gap-2" id="tags-chips"></div>
        <input type="text" class="form-control border-0 px-0 mt-2" id="tags-input" placeholder="<?= htmlspecialchars((string) ($tr['placeholder'] ?? 'Type a tag then comma or space'), ENT_QUOTES, 'UTF-8') ?>" autocomplete="off">
        <div class="list-group mt-2" id="tags-suggest"></div>
      </div>
    </div>
    <div class="col-12 d-flex justify-content-end"><button class="btn btn-primary" type="submit"><?= htmlspecialchars((string) ($tr['sync'] ?? 'Sync tags'), ENT_QUOTES, 'UTF-8') ?></button></div>
  </form>
</div></section>

<section class="card"><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>ID</th><th>Name</th><th>Slug</th><th><?= htmlspecialchars((string) ($tr['usage'] ?? 'Usage'), ENT_QUOTES, 'UTF-8') ?></th></tr></thead><tbody>
<?php if ($rows === []): ?><tr><td colspan="4" class="text-center py-5 text-body-secondary"><?= htmlspecialchars((string) ($tr['empty'] ?? 'No tags found'), ENT_QUOTES, 'UTF-8') ?></td></tr><?php else: ?>
<?php foreach ($rows as $r): ?>
<tr><td>#<?= (int) ($r['id'] ?? 0) ?></td><td><?= htmlspecialchars((string) ($r['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td><td><code><?= htmlspecialchars((string) ($r['slug'] ?? ''), ENT_QUOTES, 'UTF-8') ?></code></td><td><span class="badge text-bg-secondary"><?= (int) ($r['usage_count'] ?? 0) ?></span></td></tr>
<?php endforeach; ?>
<?php endif; ?>
</tbody></table></div></section>
<?php
$content = (string) ob_get_clean();

ob_start();
?>
<script>
(() => {
  const hidden = document.getElementById('tags-csv');
  const input = document.getElementById('tags-input');
  const chips = document.getElementById('tags-chips');
  const suggest = document.getElementById('tags-suggest');
  if (!hidden || !input || !chips || !suggest) return;

  let tags = hidden.value ? hidden.value.split(',').map((v) => v.trim()).filter(Boolean) : [];
  tags = [...new Set(tags.map((t) => t.toLowerCase()))];

  const sync = () => {
    hidden.value = tags.join(', ');
    chips.innerHTML = tags.map((tag) => '<span class="badge text-bg-light border">' + tag + ' <button type="button" class="btn btn-sm p-0 ms-1" data-remove="' + tag + '">x</button></span>').join('');
    chips.querySelectorAll('button[data-remove]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const t = String(btn.getAttribute('data-remove') || '');
        tags = tags.filter((x) => x !== t);
        sync();
      });
    });
  };

  const pushToken = (raw) => {
    const token = String(raw || '').trim().toLowerCase();
    if (!token) return;
    if (!tags.includes(token)) tags.push(token);
  };

  const consumeInput = () => {
    const parts = input.value.split(/[\s,]+/).map((v) => v.trim()).filter(Boolean);
    parts.forEach(pushToken);
    input.value = '';
    suggest.innerHTML = '';
    sync();
  };

  const fetchSuggest = async (q) => {
    if (q.length < 2) { suggest.innerHTML = ''; return; }
    try {
      const url = '<?= htmlspecialchars($adminBase . '/modules/tags-bridge/suggest', ENT_QUOTES, 'UTF-8') ?>?q=' + encodeURIComponent(q);
      const res = await fetch(url, { credentials: 'same-origin' });
      const data = await res.json();
      const items = Array.isArray(data.items) ? data.items : [];
      suggest.innerHTML = items.map((item) => '<button type="button" class="list-group-item list-group-item-action" data-tag="' + String(item.name || '') + '">' + String(item.name || '') + ' <small class="text-body-secondary">(' + Number(item.usage_count || 0) + ')</small></button>').join('');
      suggest.querySelectorAll('button[data-tag]').forEach((btn) => {
        btn.addEventListener('click', () => {
          pushToken(String(btn.getAttribute('data-tag') || ''));
          input.value = '';
          suggest.innerHTML = '';
          sync();
        });
      });
    } catch (_e) {
      suggest.innerHTML = '';
    }
  };

  input.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' || e.key === ',' || e.key === ' ') {
      e.preventDefault();
      consumeInput();
    }
  });
  input.addEventListener('blur', () => { if (input.value.trim() !== '') consumeInput(); });
  input.addEventListener('input', () => { fetchSuggest(input.value.trim()); });

  sync();
})();
</script>
<?php
$scripts = (string) ob_get_clean();
require CATMIN_ADMIN . '/views/layouts/admin.php';
