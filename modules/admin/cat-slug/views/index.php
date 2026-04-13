<?php

declare(strict_types=1);

use Core\security\CsrfManager;

$rows = isset($rows) && is_array($rows) ? $rows : [];
$message = isset($message) ? trim((string) $message) : '';
$messageType = isset($messageType) ? trim((string) $messageType) : 'info';
$suggested = isset($suggested) ? trim((string) $suggested) : '';
$tr = isset($tr) && is_array($tr) ? $tr : [];
$adminBase = isset($adminBase) ? (string) $adminBase : '/admin';
$csrf = htmlspecialchars((new CsrfManager())->token(), ENT_QUOTES, 'UTF-8');

$pageTitle = (string) ($tr['title'] ?? 'Slug Registry');
$pageDescription = (string) ($tr['description'] ?? 'Central slug registry');
$activeNav = 'cat-slug.registry';
$breadcrumbs = [
    ['label' => __('common.admin'), 'href' => $adminBase . '/'],
    ['label' => __('nav.modules')],
    ['label' => (string) ($tr['title'] ?? 'Slug Registry')],
];

ob_start();
?>
<?php if ($message !== ''): ?>
<section class="alert alert-<?= htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8') ?> mb-3"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></section>
<?php endif; ?>

<section class="card mb-3"><div class="card-body">
    <h2 class="h5 mb-2"><?= htmlspecialchars((string) ($tr['generate_title'] ?? 'Generate and reserve a slug'), ENT_QUOTES, 'UTF-8') ?></h2>
    <form method="post" action="<?= htmlspecialchars($adminBase . '/modules/slug-bridge/generate', ENT_QUOTES, 'UTF-8') ?>" class="row g-2">
        <input type="hidden" name="_csrf" value="<?= $csrf ?>">
        <div class="col-12 col-md-3"><label class="form-label"><?= htmlspecialchars((string) ($tr['entity_type'] ?? 'Entity type'), ENT_QUOTES, 'UTF-8') ?></label><input class="form-control" name="entity_type" placeholder="page" required></div>
        <div class="col-12 col-md-2"><label class="form-label"><?= htmlspecialchars((string) ($tr['entity_id'] ?? 'Entity ID'), ENT_QUOTES, 'UTF-8') ?></label><input class="form-control" type="number" min="1" name="entity_id" required></div>
        <div class="col-12 col-md-3"><label class="form-label"><?= htmlspecialchars((string) ($tr['source_text'] ?? 'Source text'), ENT_QUOTES, 'UTF-8') ?></label><input class="form-control" name="source_text" placeholder="<?= htmlspecialchars((string) ($tr['source_placeholder'] ?? 'My title'), ENT_QUOTES, 'UTF-8') ?>" required></div>
        <div class="col-12 col-md-2"><label class="form-label"><?= htmlspecialchars((string) ($tr['scope'] ?? 'Scope'), ENT_QUOTES, 'UTF-8') ?></label><input class="form-control" name="scope_key" value="global"></div>
        <div class="col-12 col-md-2"><label class="form-label"><?= htmlspecialchars((string) ($tr['manual_slug'] ?? 'Manual slug'), ENT_QUOTES, 'UTF-8') ?></label><input class="form-control" name="manual_slug" placeholder="<?= htmlspecialchars((string) ($tr['manual_placeholder'] ?? 'optional'), ENT_QUOTES, 'UTF-8') ?>"></div>
        <div class="col-12 d-grid d-md-flex justify-content-md-end"><button class="btn btn-primary" type="submit"><?= htmlspecialchars((string) ($tr['generate'] ?? 'Generate'), ENT_QUOTES, 'UTF-8') ?></button></div>
    </form>
    <?php if ($suggested !== ''): ?><p class="small text-body-secondary mt-2 mb-0"><?= htmlspecialchars((string) ($tr['slug_selected'] ?? 'Selected slug'), ENT_QUOTES, 'UTF-8') ?>: <code><?= htmlspecialchars($suggested, ENT_QUOTES, 'UTF-8') ?></code></p><?php endif; ?>
</div></section>

<section class="card mb-3"><div class="card-body">
    <h2 class="h6 mb-2"><?= htmlspecialchars((string) ($tr['validate_title'] ?? 'Validate a slug'), ENT_QUOTES, 'UTF-8') ?></h2>
    <form method="post" action="<?= htmlspecialchars($adminBase . '/modules/slug-bridge/validate', ENT_QUOTES, 'UTF-8') ?>" class="row g-2 align-items-end">
        <input type="hidden" name="_csrf" value="<?= $csrf ?>">
        <div class="col-12 col-md-5"><label class="form-label"><?= htmlspecialchars((string) ($tr['slug_label'] ?? 'Slug'), ENT_QUOTES, 'UTF-8') ?></label><input class="form-control" name="slug" placeholder="my-slug" required></div>
        <div class="col-12 col-md-4"><label class="form-label"><?= htmlspecialchars((string) ($tr['scope'] ?? 'Scope'), ENT_QUOTES, 'UTF-8') ?></label><input class="form-control" name="scope_key" value="global"></div>
        <div class="col-12 col-md-3 d-grid"><button class="btn btn-outline-primary" type="submit"><?= htmlspecialchars((string) ($tr['validate'] ?? 'Check'), ENT_QUOTES, 'UTF-8') ?></button></div>
    </form>
</div></section>

<section class="card"><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>ID</th><th><?= htmlspecialchars((string) ($tr['entity_label'] ?? 'Entity'), ENT_QUOTES, 'UTF-8') ?></th><th><?= htmlspecialchars((string) ($tr['slug_label'] ?? 'Slug'), ENT_QUOTES, 'UTF-8') ?></th><th><?= htmlspecialchars((string) ($tr['scope'] ?? 'Scope'), ENT_QUOTES, 'UTF-8') ?></th><th><?= htmlspecialchars((string) ($tr['primary_label'] ?? 'Primary'), ENT_QUOTES, 'UTF-8') ?></th><th><?= htmlspecialchars((string) ($tr['created_label'] ?? 'Created'), ENT_QUOTES, 'UTF-8') ?></th></tr></thead><tbody>
<?php if ($rows === []): ?><tr><td colspan="6" class="text-center py-5 text-body-secondary"><?= htmlspecialchars((string) ($tr['empty'] ?? 'No slug stored.'), ENT_QUOTES, 'UTF-8') ?></td></tr><?php else: ?>
<?php foreach ($rows as $r): ?>
<tr>
  <td>#<?= (int)($r['id'] ?? 0) ?></td>
  <td><?= htmlspecialchars((string)($r['entity_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?>:<?= (int)($r['entity_id'] ?? 0) ?></td>
  <td><code><?= htmlspecialchars((string)($r['slug'] ?? ''), ENT_QUOTES, 'UTF-8') ?></code></td>
  <td><?= htmlspecialchars((string)($r['scope_key'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
  <td><?= ((int)($r['is_primary'] ?? 0) === 1) ? htmlspecialchars((string) ($tr['yes'] ?? 'yes'), ENT_QUOTES, 'UTF-8') : htmlspecialchars((string) ($tr['no'] ?? 'no'), ENT_QUOTES, 'UTF-8') ?></td>
  <td><?= htmlspecialchars((string)($r['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
</tr>
<?php endforeach; ?>
<?php endif; ?>
</tbody></table></div></section>
<?php
$content = (string) ob_get_clean();
require CATMIN_ADMIN . '/views/layouts/admin.php';
