<?php

declare(strict_types=1);

use Core\security\CsrfManager;

$dashboard = isset($dashboard) && is_array($dashboard) ? $dashboard : ['stats' => [], 'tree' => [], 'selector' => []];
$stats = isset($dashboard['stats']) && is_array($dashboard['stats']) ? $dashboard['stats'] : [];
$tree = isset($dashboard['tree']) && is_array($dashboard['tree']) ? $dashboard['tree'] : [];
$selector = isset($dashboard['selector']) && is_array($dashboard['selector']) ? $dashboard['selector'] : [];
$selectedIds = isset($selectedIds) && is_array($selectedIds) ? $selectedIds : [];
$entityType = isset($entityType) ? strtolower(trim((string) $entityType)) : 'page';
$entityId = isset($entityId) ? (int) $entityId : 0;
$message = isset($message) ? trim((string) $message) : '';
$messageType = isset($messageType) ? trim((string) $messageType) : 'info';
$tr = isset($tr) && is_array($tr) ? $tr : [];
$adminBase = isset($adminBase) ? (string) $adminBase : '/admin';
$csrf = htmlspecialchars((new CsrfManager())->token(), ENT_QUOTES, 'UTF-8');

$pageTitle = (string) ($tr['title'] ?? 'Categories Bridge');
$pageDescription = (string) ($tr['description'] ?? 'Categories management');
$activeNav = 'cat-categories.dashboard';
$breadcrumbs = [
    ['label' => __('common.admin'), 'href' => $adminBase . '/'],
    ['label' => __('nav.modules')],
    ['label' => (string) ($tr['title'] ?? 'Categories Bridge')],
];

$renderTree = static function (array $nodes) use (&$renderTree): string {
    if ($nodes === []) {
        return '';
    }
    $html = '<ul class="list-group list-group-flush">';
    foreach ($nodes as $node) {
        $name = htmlspecialchars((string) ($node['name'] ?? ''), ENT_QUOTES, 'UTF-8');
        $usage = (int) ($node['usage_count'] ?? 0);
        $html .= '<li class="list-group-item px-0"><div class="d-flex justify-content-between align-items-center"><span>' . $name . '</span><span class="badge text-bg-secondary">' . $usage . '</span></div>';
        $children = (array) ($node['children'] ?? []);
        if ($children !== []) {
            $html .= '<div class="ms-3 mt-2">' . $renderTree($children) . '</div>';
        }
        $html .= '</li>';
    }
    $html .= '</ul>';
    return $html;
};

ob_start();
?>
<?php if ($message !== ''): ?><section class="alert alert-<?= htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8') ?> mb-3"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></section><?php endif; ?>

<section class="row g-3 mb-3">
  <div class="col-12 col-md-4"><div class="card h-100"><div class="card-body"><p class="small text-body-secondary mb-1"><?= htmlspecialchars((string) ($tr['total_categories'] ?? 'Total categories'), ENT_QUOTES, 'UTF-8') ?></p><p class="h3 mb-0"><?= (int) ($stats['total_categories'] ?? 0) ?></p></div></div></div>
  <div class="col-12 col-md-4"><div class="card h-100"><div class="card-body"><p class="small text-body-secondary mb-1"><?= htmlspecialchars((string) ($tr['total_links'] ?? 'Total links'), ENT_QUOTES, 'UTF-8') ?></p><p class="h3 mb-0"><?= (int) ($stats['total_links'] ?? 0) ?></p></div></div></div>
  <div class="col-12 col-md-4"><div class="card h-100"><div class="card-body"><p class="small text-body-secondary mb-1"><?= htmlspecialchars((string) ($tr['root_categories'] ?? 'Root categories'), ENT_QUOTES, 'UTF-8') ?></p><p class="h3 mb-0"><?= (int) ($stats['root_categories'] ?? 0) ?></p></div></div></div>
</section>

<section class="card mb-3"><div class="card-body">
  <h2 class="h5 mb-3"><?= htmlspecialchars((string) ($tr['create_section'] ?? 'Create category'), ENT_QUOTES, 'UTF-8') ?></h2>
  <form method="post" action="<?= htmlspecialchars($adminBase . '/modules/categories-bridge/create', ENT_QUOTES, 'UTF-8') ?>" class="row g-2">
    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
    <div class="col-12 col-md-4"><label class="form-label"><?= htmlspecialchars((string) ($tr['name'] ?? 'Name'), ENT_QUOTES, 'UTF-8') ?></label><input class="form-control" name="name" required></div>
    <div class="col-12 col-md-4"><label class="form-label"><?= htmlspecialchars((string) ($tr['parent'] ?? 'Parent'), ENT_QUOTES, 'UTF-8') ?></label><select class="form-select" name="parent_id"><option value="">-</option><?php foreach ($selector as $row): ?><option value="<?= (int) ($row['id'] ?? 0) ?>"><?= htmlspecialchars((string) ($row['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
    <div class="col-12 col-md-2"><label class="form-label"><?= htmlspecialchars((string) ($tr['order'] ?? 'Order'), ENT_QUOTES, 'UTF-8') ?></label><input class="form-control" type="number" name="sort_order" value="0"></div>
    <div class="col-12 col-md-2 d-grid align-items-end"><button class="btn btn-primary mt-md-4" type="submit"><?= htmlspecialchars((string) ($tr['create'] ?? 'Create category'), ENT_QUOTES, 'UTF-8') ?></button></div>
  </form>
</div></section>

<section class="card mb-3"><div class="card-body">
  <h2 class="h5 mb-3"><?= htmlspecialchars((string) ($tr['selector_section'] ?? 'Entity category selector'), ENT_QUOTES, 'UTF-8') ?></h2>
  <form method="post" action="<?= htmlspecialchars($adminBase . '/modules/categories-bridge/sync', ENT_QUOTES, 'UTF-8') ?>" class="row g-2">
    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
    <div class="col-12 col-md-3"><label class="form-label"><?= htmlspecialchars((string) ($tr['entity_type'] ?? 'Entity type'), ENT_QUOTES, 'UTF-8') ?></label><input class="form-control" name="entity_type" value="<?= htmlspecialchars($entityType, ENT_QUOTES, 'UTF-8') ?>" required></div>
    <div class="col-12 col-md-2"><label class="form-label"><?= htmlspecialchars((string) ($tr['entity_id'] ?? 'Entity ID'), ENT_QUOTES, 'UTF-8') ?></label><input class="form-control" type="number" min="1" name="entity_id" value="<?= $entityId ?>" required></div>
    <div class="col-12 col-md-7"><label class="form-label"><?= htmlspecialchars((string) ($tr['selector'] ?? 'Category selector'), ENT_QUOTES, 'UTF-8') ?></label><select class="form-select" name="category_ids[]" multiple size="7"><?php foreach ($selector as $row): ?><?php $id = (int) ($row['id'] ?? 0); ?><option value="<?= $id ?>" <?= in_array($id, $selectedIds, true) ? 'selected' : '' ?>><?= htmlspecialchars((string) ($row['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
    <div class="col-12 d-flex justify-content-end"><button class="btn btn-outline-primary" type="submit"><?= htmlspecialchars((string) ($tr['sync'] ?? 'Sync categories'), ENT_QUOTES, 'UTF-8') ?></button></div>
  </form>
</div></section>

<section class="card"><div class="card-body">
  <h2 class="h5 mb-3"><?= htmlspecialchars((string) ($tr['tree_section'] ?? 'Tree'), ENT_QUOTES, 'UTF-8') ?></h2>
  <?php if ($tree === []): ?>
    <p class="text-body-secondary mb-0"><?= htmlspecialchars((string) ($tr['empty'] ?? 'No categories'), ENT_QUOTES, 'UTF-8') ?></p>
  <?php else: ?>
    <?= $renderTree($tree) ?>
  <?php endif; ?>
</div></section>
<?php
$content = (string) ob_get_clean();
require CATMIN_ADMIN . '/views/layouts/admin.php';
