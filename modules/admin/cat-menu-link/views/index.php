<?php

declare(strict_types=1);

use Core\security\CsrfManager;

$state = isset($state) && is_array($state) ? $state : [];
$stats = isset($state['stats']) && is_array($state['stats']) ? $state['stats'] : [];
$menus = isset($state['menus']) && is_array($state['menus']) ? $state['menus'] : [];
$items = isset($state['items']) && is_array($state['items']) ? $state['items'] : [];
$allItems = isset($state['all_items']) && is_array($state['all_items']) ? $state['all_items'] : [];
$menuKey = isset($state['menu_key']) ? trim((string) $state['menu_key']) : 'main_nav';
$snippet = isset($state['breadcrumb_snippet']) ? (string) $state['breadcrumb_snippet'] : '';
$message = isset($message) ? trim((string) $message) : '';
$messageType = isset($messageType) ? trim((string) $messageType) : 'info';
$tr = isset($tr) && is_array($tr) ? $tr : [];
$adminBase = isset($adminBase) ? (string) $adminBase : '/admin';
$csrf = htmlspecialchars((new CsrfManager())->token(), ENT_QUOTES, 'UTF-8');

$pageTitle = (string) ($tr['title'] ?? 'Menus');
$pageDescription = (string) ($tr['description'] ?? 'Menu bridge');
$activeNav = 'cat-menu-link.dashboard';
$breadcrumbs = [
    ['label' => __('common.admin'), 'href' => $adminBase . '/'],
    ['label' => __('nav.modules')],
    ['label' => (string) ($tr['title'] ?? 'Menus')],
];

ob_start();
?>
<?php if ($message !== ''): ?><section class="alert alert-<?= htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8') ?> mb-3"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></section><?php endif; ?>

<section class="cat-module-stats mb-3">
  <div class="cat-module-stat-col"><div class="card h-100"><div class="card-body"><p class="small text-body-secondary mb-1"><?= htmlspecialchars((string) ($tr['stats_menus'] ?? 'Menus'), ENT_QUOTES, 'UTF-8') ?></p><p class="h3 mb-0"><?= (int) ($stats['menus'] ?? 0) ?></p></div></div></div>
  <div class="cat-module-stat-col"><div class="card h-100"><div class="card-body"><p class="small text-body-secondary mb-1"><?= htmlspecialchars((string) ($tr['stats_items'] ?? 'Items'), ENT_QUOTES, 'UTF-8') ?></p><p class="h3 mb-0"><?= (int) ($stats['items'] ?? 0) ?></p></div></div></div>
  <div class="cat-module-stat-col"><div class="card h-100"><div class="card-body"><p class="small text-body-secondary mb-1"><?= htmlspecialchars((string) ($tr['stats_visible'] ?? 'Visible'), ENT_QUOTES, 'UTF-8') ?></p><p class="h3 mb-0"><?= (int) ($stats['visible'] ?? 0) ?></p></div></div></div>
</section>

<section class="card mb-3"><div class="card-body">
  <form method="get" class="row g-2 align-items-end">
    <div class="col-12 col-md-4">
      <label class="form-label"><?= htmlspecialchars((string) ($tr['menu_key'] ?? 'Menu key'), ENT_QUOTES, 'UTF-8') ?></label>
      <input class="form-control" name="menu_key" value="<?= htmlspecialchars($menuKey, ENT_QUOTES, 'UTF-8') ?>" list="menu-key-list">
      <datalist id="menu-key-list">
        <?php foreach ($menus as $menu): ?>
          <?php $key = trim((string) ($menu['menu_key'] ?? '')); ?>
          <?php if ($key !== ''): ?>
            <option value="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>"></option>
          <?php endif; ?>
        <?php endforeach; ?>
      </datalist>
    </div>
    <div class="col-12 col-md-2 d-grid"><button class="btn btn-outline-primary" type="submit"><?= htmlspecialchars((string) ($tr['load'] ?? 'Load'), ENT_QUOTES, 'UTF-8') ?></button></div>
  </form>
</div></section>

<section class="card mb-3"><div class="card-body">
  <h2 class="h5 mb-3"><?= htmlspecialchars((string) ($tr['attach'] ?? 'Attach to menu'), ENT_QUOTES, 'UTF-8') ?></h2>
  <form method="post" action="<?= htmlspecialchars($adminBase . '/modules/menu-link/attach', ENT_QUOTES, 'UTF-8') ?>" class="row g-2">
    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
    <div class="col-12 col-md-2"><label class="form-label"><?= htmlspecialchars((string) ($tr['menu_key'] ?? 'Menu key'), ENT_QUOTES, 'UTF-8') ?></label><input class="form-control" name="menu_key" value="<?= htmlspecialchars($menuKey, ENT_QUOTES, 'UTF-8') ?>" list="menu-key-list" required></div>
    <div class="col-12 col-md-2"><label class="form-label"><?= htmlspecialchars((string) ($tr['entity_type'] ?? 'Entity type'), ENT_QUOTES, 'UTF-8') ?></label><select class="form-select" name="entity_type" required><option value="page">page</option><option value="article">article</option><option value="app">app</option><option value="custom">custom</option></select></div>
    <div class="col-12 col-md-2"><label class="form-label"><?= htmlspecialchars((string) ($tr['entity_id'] ?? 'Entity ID'), ENT_QUOTES, 'UTF-8') ?></label><input class="form-control" type="number" min="0" name="entity_id" required></div>
    <div class="col-12 col-md-2"><label class="form-label"><?= htmlspecialchars((string) ($tr['parent_item'] ?? 'Parent item'), ENT_QUOTES, 'UTF-8') ?></label><select class="form-select" name="parent_item_id"><option value="0">-</option><?php foreach ($allItems as $row): ?><option value="<?= (int) ($row['id'] ?? 0) ?>">#<?= (int) ($row['id'] ?? 0) ?> <?= htmlspecialchars((string) ($row['label_override'] ?? ($row['entity_type'] ?? '') . ':' . (int) ($row['entity_id'] ?? 0)), ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
    <div class="col-12 col-md-2"><label class="form-label"><?= htmlspecialchars((string) ($tr['sort_order'] ?? 'Order'), ENT_QUOTES, 'UTF-8') ?></label><input class="form-control" type="number" name="sort_order" value="0"></div>
    <div class="col-12 col-md-2"><label class="form-label"><?= htmlspecialchars((string) ($tr['visible'] ?? 'Visible'), ENT_QUOTES, 'UTF-8') ?></label><select class="form-select" name="is_visible"><option value="1">1</option><option value="0">0</option></select></div>
    <div class="col-12 col-md-4"><label class="form-label"><?= htmlspecialchars((string) ($tr['label_override'] ?? 'Label override'), ENT_QUOTES, 'UTF-8') ?></label><input class="form-control" name="label_override"></div>
    <div class="col-12 col-md-4"><label class="form-label"><?= htmlspecialchars((string) ($tr['target_url'] ?? 'Custom URL'), ENT_QUOTES, 'UTF-8') ?></label><input class="form-control" name="target_url" placeholder="/about"></div>
    <div class="col-12 col-md-2"><label class="form-label"><?= htmlspecialchars((string) ($tr['link_type'] ?? 'Link type'), ENT_QUOTES, 'UTF-8') ?></label><select class="form-select" name="link_type"><option value="entity_link">entity_link</option><option value="custom_url">custom_url</option></select></div>
    <div class="col-12 col-md-2 d-grid align-items-end"><button class="btn btn-primary mt-md-4" type="submit"><?= htmlspecialchars((string) ($tr['attach'] ?? 'Attach'), ENT_QUOTES, 'UTF-8') ?></button></div>
  </form>
</div></section>

<section class="card mb-3"><div class="card-body">
  <h2 class="h5 mb-3"><?= htmlspecialchars((string) ($tr['items_title'] ?? 'Menu items'), ENT_QUOTES, 'UTF-8') ?> · <code><?= htmlspecialchars($menuKey, ENT_QUOTES, 'UTF-8') ?></code></h2>
  <?php if ($items === []): ?>
    <p class="text-body-secondary mb-0"><?= htmlspecialchars((string) ($tr['empty'] ?? 'No items'), ENT_QUOTES, 'UTF-8') ?></p>
  <?php else: ?>
    <form method="post" action="<?= htmlspecialchars($adminBase . '/modules/menu-link/reorder', ENT_QUOTES, 'UTF-8') ?>" id="menu-reorder-form">
      <input type="hidden" name="_csrf" value="<?= $csrf ?>">
      <input type="hidden" name="menu_key" value="<?= htmlspecialchars($menuKey, ENT_QUOTES, 'UTF-8') ?>">
      <input type="hidden" name="order_json" id="menu-order-json" value="[]">
      <div class="list-group" id="menu-items-sortable">
        <?php foreach ($items as $item): ?>
          <?php $id = (int) ($item['id'] ?? 0); ?>
          <div class="list-group-item d-flex justify-content-between align-items-center gap-3" draggable="true" data-item-id="<?= $id ?>" data-parent-id="<?= (int) ($item['parent_item_id'] ?? 0) ?>">
            <div class="d-flex align-items-center gap-2">
              <i class="bi bi-grip-vertical text-body-secondary"></i>
              <div>
                <div class="fw-semibold">#<?= $id ?> · <?= htmlspecialchars((string) ($item['label_override'] ?? ''), ENT_QUOTES, 'UTF-8') !== '' ? htmlspecialchars((string) ($item['label_override'] ?? ''), ENT_QUOTES, 'UTF-8') : htmlspecialchars((string) (($item['entity_type'] ?? '') . ':' . (int) ($item['entity_id'] ?? 0)), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="small text-body-secondary"><?= htmlspecialchars((string) ($item['link_type'] ?? 'entity_link'), ENT_QUOTES, 'UTF-8') ?> · parent: <?= (int) ($item['parent_item_id'] ?? 0) ?> · order: <?= (int) ($item['sort_order'] ?? 0) ?></div>
              </div>
            </div>
            <div class="d-flex align-items-center gap-2">
              <span class="badge <?= (int) ($item['is_visible'] ?? 0) === 1 ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= (int) ($item['is_visible'] ?? 0) === 1 ? 'visible' : 'hidden' ?></span>
              <button type="button" class="btn btn-sm btn-outline-danger cat-menu-delete-btn" data-delete-id="<?= $id ?>"><?= htmlspecialchars((string) ($tr['delete'] ?? 'Delete'), ENT_QUOTES, 'UTF-8') ?></button>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="d-flex justify-content-end mt-3"><button class="btn btn-outline-primary" type="submit"><?= htmlspecialchars((string) ($tr['save_order'] ?? 'Save order'), ENT_QUOTES, 'UTF-8') ?></button></div>
    </form>
    <form method="post" action="<?= htmlspecialchars($adminBase . '/modules/menu-link/delete', ENT_QUOTES, 'UTF-8') ?>" id="menu-delete-form" class="d-none" data-confirm="<?= htmlspecialchars((string) ($tr['confirm_delete'] ?? 'Confirmer la suppression de cet item ?'), ENT_QUOTES, 'UTF-8') ?>">
      <input type="hidden" name="_csrf" value="<?= $csrf ?>">
      <input type="hidden" name="menu_key" value="<?= htmlspecialchars($menuKey, ENT_QUOTES, 'UTF-8') ?>">
      <input type="hidden" name="id" id="menu-delete-id" value="0">
    </form>
  <?php endif; ?>
</div></section>

<section class="card"><div class="card-body">
  <h2 class="h5 mb-2"><?= htmlspecialchars((string) ($tr['snippet_title'] ?? 'Snippet'), ENT_QUOTES, 'UTF-8') ?></h2>
  <p class="text-body-secondary"><?= htmlspecialchars((string) ($tr['snippet_help'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
  <pre class="mb-0"><code><?= htmlspecialchars($snippet, ENT_QUOTES, 'UTF-8') ?></code></pre>
</div></section>
<?php
$content = (string) ob_get_clean();

ob_start();
?>
<script src="<?= htmlspecialchars($adminBase . '/modules/menu-link/assets/admin.js?v=1', ENT_QUOTES, 'UTF-8') ?>"></script>
<?php
$scripts = (string) ob_get_clean();
require CATMIN_ADMIN . '/views/layouts/admin.php';
