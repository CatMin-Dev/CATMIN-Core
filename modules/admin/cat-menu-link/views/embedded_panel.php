<?php

declare(strict_types=1);

$state = isset($state) && is_array($state) ? $state : [];
$menuKey = isset($state['menu_key']) ? trim((string) $state['menu_key']) : 'main_nav';
$items = isset($state['all_items']) && is_array($state['all_items']) ? $state['all_items'] : [];
$tr = isset($tr) && is_array($tr) ? $tr : [];
?>
<div class="cat-menu-panel">
  <label class="form-label fw-semibold"><i class="bi bi-diagram-3 me-1"></i><?= htmlspecialchars((string) ($tr['title'] ?? 'Menus'), ENT_QUOTES, 'UTF-8') ?></label>
  <div class="row g-2">
    <div class="col-12 col-md-4"><label class="form-label"><?= htmlspecialchars((string) ($tr['menu_key'] ?? 'Menu key'), ENT_QUOTES, 'UTF-8') ?></label><input class="form-control" name="menu_key" value="<?= htmlspecialchars($menuKey, ENT_QUOTES, 'UTF-8') ?>"></div>
    <div class="col-12 col-md-4"><label class="form-label"><?= htmlspecialchars((string) ($tr['label_override'] ?? 'Label override'), ENT_QUOTES, 'UTF-8') ?></label><input class="form-control" name="menu_label_override"></div>
    <div class="col-12 col-md-4"><label class="form-label"><?= htmlspecialchars((string) ($tr['parent_item'] ?? 'Parent item'), ENT_QUOTES, 'UTF-8') ?></label><select class="form-select" name="menu_parent_item_id"><option value="0">-</option><?php foreach ($items as $row): ?><option value="<?= (int) ($row['id'] ?? 0) ?>">#<?= (int) ($row['id'] ?? 0) ?> <?= htmlspecialchars((string) ($row['label_override'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
    <div class="col-12 col-md-4"><label class="form-label"><?= htmlspecialchars((string) ($tr['sort_order'] ?? 'Order'), ENT_QUOTES, 'UTF-8') ?></label><input class="form-control" type="number" name="menu_sort_order" value="0"></div>
    <div class="col-12 col-md-4"><label class="form-label"><?= htmlspecialchars((string) ($tr['visible'] ?? 'Visible'), ENT_QUOTES, 'UTF-8') ?></label><select class="form-select" name="menu_visible"><option value="1">1</option><option value="0">0</option></select></div>
  </div>
</div>
