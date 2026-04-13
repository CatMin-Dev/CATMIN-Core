<?php

declare(strict_types=1);

use Core\security\CsrfManager;

$dashboard = isset($dashboard) && is_array($dashboard) ? $dashboard : ['stats' => [], 'needs_attention' => [], 'recent' => []];
$record = isset($record) && is_array($record) ? $record : [];
$preview = isset($preview) && is_array($preview) ? $preview : [];
$message = isset($message) ? trim((string) $message) : '';
$messageType = isset($messageType) ? trim((string) $messageType) : 'info';
$auditSummary = isset($auditSummary) ? trim((string) $auditSummary) : '';
$tr = isset($tr) && is_array($tr) ? $tr : [];
$adminBase = isset($adminBase) ? (string) $adminBase : '/admin';
$csrf = htmlspecialchars((new CsrfManager())->token(), ENT_QUOTES, 'UTF-8');
$stats = isset($dashboard['stats']) && is_array($dashboard['stats']) ? $dashboard['stats'] : [];
$attention = isset($dashboard['needs_attention']) && is_array($dashboard['needs_attention']) ? $dashboard['needs_attention'] : [];
$recent = isset($dashboard['recent']) && is_array($dashboard['recent']) ? $dashboard['recent'] : [];

$pageTitle = (string) ($tr['title'] ?? 'SEO Meta Bridge');
$pageDescription = (string) ($tr['description'] ?? 'SEO metadata bridge');
$activeNav = 'cat-seo-meta.dashboard';
$breadcrumbs = [
    ['label' => __('common.admin'), 'href' => $adminBase . '/'],
    ['label' => __('nav.modules')],
    ['label' => (string) ($tr['title'] ?? 'SEO Meta Bridge')],
];

ob_start();
?>
<?php if ($message !== ''): ?>
<section class="alert alert-<?= htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8') ?> mb-3"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></section>
<?php endif; ?>
<?php if ($auditSummary !== ''): ?>
<section class="alert alert-light border mb-3"><strong><?= htmlspecialchars((string) ($tr['audit_summary'] ?? 'Audit summary'), ENT_QUOTES, 'UTF-8') ?>:</strong> <?= htmlspecialchars($auditSummary, ENT_QUOTES, 'UTF-8') ?></section>
<?php endif; ?>

<section class="row g-3 mb-3">
  <div class="col-12 col-md-4"><div class="card h-100"><div class="card-body"><p class="small text-body-secondary mb-1"><?= htmlspecialchars((string) ($tr['total'] ?? 'Indexed contents'), ENT_QUOTES, 'UTF-8') ?></p><p class="h3 mb-0"><?= (int) ($stats['total'] ?? 0) ?></p></div></div></div>
  <div class="col-12 col-md-4"><div class="card h-100"><div class="card-body"><p class="small text-body-secondary mb-1"><?= htmlspecialchars((string) ($tr['avg_score'] ?? 'Average score'), ENT_QUOTES, 'UTF-8') ?></p><p class="h3 mb-0"><?= (int) ($stats['avg_score'] ?? 0) ?>/100</p></div></div></div>
  <div class="col-12 col-md-4"><div class="card h-100"><div class="card-body"><p class="small text-body-secondary mb-1"><?= htmlspecialchars((string) ($tr['need_attention'] ?? 'Need attention'), ENT_QUOTES, 'UTF-8') ?></p><p class="h3 mb-0 text-warning"><?= (int) ($stats['need_attention'] ?? 0) ?></p></div></div></div>
</section>

<section class="card mb-3"><div class="card-body">
    <h2 class="h5 mb-3"><?= htmlspecialchars((string) ($tr['editor'] ?? 'SEO editor'), ENT_QUOTES, 'UTF-8') ?></h2>
    <form method="post" action="<?= htmlspecialchars($adminBase . '/modules/seo-meta/save', ENT_QUOTES, 'UTF-8') ?>" class="row g-2">
        <input type="hidden" name="_csrf" value="<?= $csrf ?>">
        <div class="col-12 col-md-3"><label class="form-label"><?= htmlspecialchars((string) ($tr['entity_type'] ?? 'Entity type'), ENT_QUOTES, 'UTF-8') ?></label><input class="form-control" name="entity_type" value="<?= htmlspecialchars((string) ($record['entity_type'] ?? 'page'), ENT_QUOTES, 'UTF-8') ?>" required></div>
        <div class="col-12 col-md-2"><label class="form-label"><?= htmlspecialchars((string) ($tr['entity_id'] ?? 'Entity ID'), ENT_QUOTES, 'UTF-8') ?></label><input class="form-control" type="number" min="1" name="entity_id" value="<?= (int) ($record['entity_id'] ?? 0) ?>" required></div>
        <div class="col-12 col-md-7"><label class="form-label"><?= htmlspecialchars((string) ($tr['seo_title'] ?? 'SEO title'), ENT_QUOTES, 'UTF-8') ?></label><input class="form-control" name="seo_title" value="<?= htmlspecialchars((string) ($record['seo_title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
        <div class="col-12"><label class="form-label"><?= htmlspecialchars((string) ($tr['meta_description'] ?? 'Meta description'), ENT_QUOTES, 'UTF-8') ?></label><textarea class="form-control" name="meta_description" rows="3"><?= htmlspecialchars((string) ($record['meta_description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea></div>
        <div class="col-12 col-md-6"><label class="form-label"><?= htmlspecialchars((string) ($tr['canonical_url'] ?? 'Canonical URL'), ENT_QUOTES, 'UTF-8') ?></label><input class="form-control" name="canonical_url" value="<?= htmlspecialchars((string) ($record['canonical_url'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
        <div class="col-12 col-md-3"><label class="form-label"><?= htmlspecialchars((string) ($tr['focus_keyword'] ?? 'Focus keyword'), ENT_QUOTES, 'UTF-8') ?></label><input class="form-control" name="focus_keyword" value="<?= htmlspecialchars((string) ($record['focus_keyword'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
        <div class="col-12 col-md-3"><label class="form-label"><?= htmlspecialchars((string) ($tr['og_image_media_id'] ?? 'OG image media ID'), ENT_QUOTES, 'UTF-8') ?></label><input class="form-control" type="number" min="0" name="og_image_media_id" value="<?= (int) ($record['og_image_media_id'] ?? 0) ?>"></div>
        <div class="col-12 col-md-6"><label class="form-label"><?= htmlspecialchars((string) ($tr['og_title'] ?? 'OG title'), ENT_QUOTES, 'UTF-8') ?></label><input class="form-control" name="og_title" value="<?= htmlspecialchars((string) ($record['og_title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
        <div class="col-12 col-md-6"><label class="form-label"><?= htmlspecialchars((string) ($tr['og_description'] ?? 'OG description'), ENT_QUOTES, 'UTF-8') ?></label><input class="form-control" name="og_description" value="<?= htmlspecialchars((string) ($record['og_description'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
        <div class="col-12 d-flex gap-3 align-items-center">
            <label class="form-check-label"><input class="form-check-input me-1" type="checkbox" name="robots_index" value="1" <?= !empty($record['robots_index']) ? 'checked' : '' ?>><?= htmlspecialchars((string) ($tr['robots_index'] ?? 'Robots index'), ENT_QUOTES, 'UTF-8') ?></label>
            <label class="form-check-label"><input class="form-check-input me-1" type="checkbox" name="robots_follow" value="1" <?= !empty($record['robots_follow']) ? 'checked' : '' ?>><?= htmlspecialchars((string) ($tr['robots_follow'] ?? 'Robots follow'), ENT_QUOTES, 'UTF-8') ?></label>
        </div>
        <div class="col-12 d-flex gap-2 justify-content-end">
            <button class="btn btn-outline-primary" formaction="<?= htmlspecialchars($adminBase . '/modules/seo-meta/audit', ENT_QUOTES, 'UTF-8') ?>" type="submit"><?= htmlspecialchars((string) ($tr['audit'] ?? 'Quick audit'), ENT_QUOTES, 'UTF-8') ?></button>
            <button class="btn btn-primary" type="submit"><?= htmlspecialchars((string) ($tr['save'] ?? 'Save'), ENT_QUOTES, 'UTF-8') ?></button>
        </div>
    </form>
</div></section>

<section class="row g-3">
  <div class="col-12 col-lg-6"><div class="card h-100"><div class="card-body">
    <h2 class="h6 mb-3"><?= htmlspecialchars((string) ($tr['attention_list'] ?? 'Incomplete contents'), ENT_QUOTES, 'UTF-8') ?></h2>
    <div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr><th>Entity</th><th>Score</th><th>Updated</th></tr></thead><tbody>
    <?php if ($attention === []): ?><tr><td colspan="3" class="text-center py-4 text-body-secondary"><?= htmlspecialchars((string) ($tr['empty'] ?? 'No SEO rows yet'), ENT_QUOTES, 'UTF-8') ?></td></tr><?php else: ?>
    <?php foreach ($attention as $row): ?>
    <tr>
      <td><a href="<?= htmlspecialchars($adminBase . '/modules/seo-meta?entity_type=' . rawurlencode((string) ($row['entity_type'] ?? '')) . '&entity_id=' . (int) ($row['entity_id'] ?? 0), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($row['entity_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?>:<?= (int) ($row['entity_id'] ?? 0) ?></a></td>
      <td><span class="badge text-bg-warning"><?= (int) ($row['seo_score'] ?? 0) ?></span></td>
      <td><?= htmlspecialchars((string) ($row['updated_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
    </tr>
    <?php endforeach; ?>
    <?php endif; ?>
    </tbody></table></div>
  </div></div></div>

  <div class="col-12 col-lg-6"><div class="card h-100"><div class="card-body">
    <h2 class="h6 mb-3"><?= htmlspecialchars((string) ($tr['preview'] ?? 'Social preview'), ENT_QUOTES, 'UTF-8') ?></h2>
    <?php
      $previewTitle = htmlspecialchars((string) ($preview['og_title'] ?? $preview['title'] ?? ''), ENT_QUOTES, 'UTF-8');
      $previewDesc = htmlspecialchars((string) ($preview['og_description'] ?? $preview['description'] ?? ''), ENT_QUOTES, 'UTF-8');
      $previewUrl = htmlspecialchars((string) ($preview['url'] ?? '/'), ENT_QUOTES, 'UTF-8');
    ?>
    <div class="border rounded p-3 bg-light-subtle">
      <p class="small text-body-secondary mb-1"><?= $previewUrl ?></p>
      <p class="fw-semibold mb-1"><?= $previewTitle ?></p>
      <p class="small mb-0 text-body-secondary"><?= $previewDesc ?></p>
    </div>

    <h3 class="h6 mt-4 mb-2"><?= htmlspecialchars((string) ($tr['recent'] ?? 'Latest updates'), ENT_QUOTES, 'UTF-8') ?></h3>
    <ul class="list-group list-group-flush">
    <?php if ($recent === []): ?>
      <li class="list-group-item px-0 text-body-secondary"><?= htmlspecialchars((string) ($tr['empty'] ?? 'No SEO rows yet'), ENT_QUOTES, 'UTF-8') ?></li>
    <?php else: ?>
    <?php foreach ($recent as $row): ?>
      <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
        <span><?= htmlspecialchars((string) ($row['entity_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?>:<?= (int) ($row['entity_id'] ?? 0) ?></span>
        <span class="badge text-bg-secondary"><?= (int) ($row['seo_score'] ?? 0) ?></span>
      </li>
    <?php endforeach; ?>
    <?php endif; ?>
    </ul>
  </div></div></div>
</section>
<?php
$content = (string) ob_get_clean();
require CATMIN_ADMIN . '/views/layouts/admin.php';
