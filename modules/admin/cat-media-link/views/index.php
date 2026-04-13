<?php

declare(strict_types=1);

use Core\security\CsrfManager;

$state = isset($state) && is_array($state) ? $state : [];
$stats = isset($state['stats']) && is_array($state['stats']) ? $state['stats'] : [];
$assets = isset($state['assets']) && is_array($state['assets']) ? $state['assets'] : [];
$usages = isset($state['usages']) && is_array($state['usages']) ? $state['usages'] : [];
$runtime = isset($state['runtime_dependencies']) && is_array($state['runtime_dependencies']) ? $state['runtime_dependencies'] : [];
$moduleDeps = isset($state['module_dependencies']) && is_array($state['module_dependencies']) ? $state['module_dependencies'] : [];
$activation = isset($state['activation_state']) && is_array($state['activation_state']) ? $state['activation_state'] : ['ok' => true, 'missing' => []];
$preview = isset($preview) && is_array($preview) ? $preview : ['links' => [], 'featured' => null];
$entityType = isset($entityType) ? strtolower(trim((string) $entityType)) : 'page';
$entityId = isset($entityId) ? (int) $entityId : 0;
$message = isset($message) ? trim((string) $message) : '';
$messageType = isset($messageType) ? trim((string) $messageType) : 'info';
$tr = isset($tr) && is_array($tr) ? $tr : [];
$adminBase = isset($adminBase) ? (string) $adminBase : '/admin';
$csrf = htmlspecialchars((new CsrfManager())->token(), ENT_QUOTES, 'UTF-8');

$pageTitle = (string) ($tr['title'] ?? 'Media');
$pageDescription = (string) ($tr['description'] ?? 'Media bridge');
$activeNav = 'cat-media-link.dashboard';
$breadcrumbs = [
    ['label' => __('common.admin'), 'href' => $adminBase . '/'],
    ['label' => __('nav.modules')],
    ['label' => (string) ($tr['title'] ?? 'Media')],
];

$depMissing = [];
foreach ($moduleDeps as $dep) {
    if (!((bool) ($dep['present'] ?? false) && (bool) ($dep['enabled'] ?? false))) {
        $depMissing[] = (string) ($dep['slug'] ?? 'unknown');
    }
}

ob_start();
?>
<?php if ($message !== ''): ?><section class="alert alert-<?= htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8') ?> mb-3"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></section><?php endif; ?>

<section class="cat-module-stats mb-3">
  <div class="cat-module-stat-col"><div class="card h-100"><div class="card-body"><p class="small text-body-secondary mb-1"><?= htmlspecialchars((string) ($tr['stats_assets'] ?? 'Total media'), ENT_QUOTES, 'UTF-8') ?></p><p class="h3 mb-0"><?= (int) ($stats['assets'] ?? 0) ?></p></div></div></div>
  <div class="cat-module-stat-col"><div class="card h-100"><div class="card-body"><p class="small text-body-secondary mb-1"><?= htmlspecialchars((string) ($tr['stats_links'] ?? 'Active links'), ENT_QUOTES, 'UTF-8') ?></p><p class="h3 mb-0"><?= (int) ($stats['links'] ?? 0) ?></p></div></div></div>
  <div class="cat-module-stat-col"><div class="card h-100"><div class="card-body"><p class="small text-body-secondary mb-1"><?= htmlspecialchars((string) ($tr['stats_featured'] ?? 'Active featured'), ENT_QUOTES, 'UTF-8') ?></p><p class="h3 mb-0"><?= (int) ($stats['featured'] ?? 0) ?></p></div></div></div>
</section>

<section class="card mb-3"><div class="card-body">
    <h2 class="h5 mb-2"><?= htmlspecialchars((string) ($tr['runtime_title'] ?? 'Runtime requirements'), ENT_QUOTES, 'UTF-8') ?></h2>
    <p class="text-body-secondary mb-3"><?= htmlspecialchars((string) ($tr['runtime_help'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
    <div class="d-flex flex-wrap gap-2 mb-3">
      <?php foreach ($runtime as $key => $ok): ?>
        <span class="badge <?= $ok ? 'text-bg-success' : 'text-bg-danger' ?>"><?= htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8') ?>: <?= htmlspecialchars((string) ($ok ? ($tr['dep_present'] ?? 'Active') : ($tr['dep_missing'] ?? 'Missing')), ENT_QUOTES, 'UTF-8') ?></span>
      <?php endforeach; ?>
    </div>

    <h3 class="h6 mb-2"><?= htmlspecialchars((string) ($tr['modules_dep'] ?? 'Module dependencies'), ENT_QUOTES, 'UTF-8') ?></h3>
    <div class="d-flex flex-wrap gap-2 mb-3">
      <?php foreach ($moduleDeps as $dep): ?>
        <?php $ok = (bool) ($dep['present'] ?? false) && (bool) ($dep['enabled'] ?? false); ?>
        <span class="badge <?= $ok ? 'text-bg-success' : 'text-bg-danger' ?>"><?= htmlspecialchars((string) ($dep['slug'] ?? ''), ENT_QUOTES, 'UTF-8') ?>: <?= htmlspecialchars((string) ($ok ? ($tr['dep_present'] ?? 'Active') : ($tr['dep_missing'] ?? 'Missing')), ENT_QUOTES, 'UTF-8') ?></span>
      <?php endforeach; ?>
    </div>

    <?php if (!(bool) ($activation['ok'] ?? false) || $depMissing !== []): ?>
      <form method="post" action="<?= htmlspecialchars($adminBase . '/modules/dependencies/resolve', ENT_QUOTES, 'UTF-8') ?>" class="d-inline">
        <input type="hidden" name="_csrf" value="<?= $csrf ?>">
        <input type="hidden" name="scope" value="admin">
        <input type="hidden" name="slug" value="cat-media-link">
        <input type="hidden" name="activate_target" value="1">
        <input type="hidden" name="return_to" value="manager">
        <button class="btn btn-danger" type="submit"><?= htmlspecialchars((string) ($tr['resolve_deps'] ?? 'Install/enable dependencies'), ENT_QUOTES, 'UTF-8') ?></button>
      </form>
    <?php endif; ?>
</div></section>

<section class="card mb-3"><div class="card-body">
  <div class="d-flex flex-wrap gap-3">
    <div class="flex-fill" style="min-width:320px;">
      <h2 class="h5 mb-3"><?= htmlspecialchars((string) ($tr['upload_title'] ?? 'Upload media'), ENT_QUOTES, 'UTF-8') ?></h2>
      <form method="post" action="<?= htmlspecialchars($adminBase . '/modules/media-link/upload', ENT_QUOTES, 'UTF-8') ?>" enctype="multipart/form-data" class="row g-2">
        <input type="hidden" name="_csrf" value="<?= $csrf ?>">
        <div class="col-12"><label class="form-label"><?= htmlspecialchars((string) ($tr['upload_file'] ?? 'File'), ENT_QUOTES, 'UTF-8') ?></label><input class="form-control" type="file" name="media_file" accept="image/*,video/*" required></div>
        <div class="col-12 col-md-6"><label class="form-label"><?= htmlspecialchars((string) ($tr['title_field'] ?? 'Title'), ENT_QUOTES, 'UTF-8') ?></label><input class="form-control" name="title"></div>
        <div class="col-12 col-md-6"><label class="form-label"><?= htmlspecialchars((string) ($tr['alt_field'] ?? 'Alt text'), ENT_QUOTES, 'UTF-8') ?></label><input class="form-control" name="alt_text"></div>
        <div class="col-12 d-grid"><button class="btn btn-primary" type="submit"><?= htmlspecialchars((string) ($tr['upload_btn'] ?? 'Upload'), ENT_QUOTES, 'UTF-8') ?></button></div>
      </form>
    </div>

    <div class="flex-fill" style="min-width:320px;">
      <h2 class="h5 mb-3"><?= htmlspecialchars((string) ($tr['url_title'] ?? 'Add remote media'), ENT_QUOTES, 'UTF-8') ?></h2>
      <form method="post" action="<?= htmlspecialchars($adminBase . '/modules/media-link/add-url', ENT_QUOTES, 'UTF-8') ?>" class="row g-2">
        <input type="hidden" name="_csrf" value="<?= $csrf ?>">
        <div class="col-12"><label class="form-label"><?= htmlspecialchars((string) ($tr['url'] ?? 'URL'), ENT_QUOTES, 'UTF-8') ?></label><input class="form-control" name="url" placeholder="https://..." required></div>
        <div class="col-12 col-md-4"><label class="form-label"><?= htmlspecialchars((string) ($tr['media_type'] ?? 'Media type'), ENT_QUOTES, 'UTF-8') ?></label><select class="form-select" name="media_type"><option value="image"><?= htmlspecialchars((string) ($tr['type_image'] ?? 'Image'), ENT_QUOTES, 'UTF-8') ?></option><option value="video"><?= htmlspecialchars((string) ($tr['type_video'] ?? 'Video'), ENT_QUOTES, 'UTF-8') ?></option></select></div>
        <div class="col-12 col-md-4"><label class="form-label"><?= htmlspecialchars((string) ($tr['title_field'] ?? 'Title'), ENT_QUOTES, 'UTF-8') ?></label><input class="form-control" name="title"></div>
        <div class="col-12 col-md-4"><label class="form-label"><?= htmlspecialchars((string) ($tr['alt_field'] ?? 'Alt text'), ENT_QUOTES, 'UTF-8') ?></label><input class="form-control" name="alt_text"></div>
        <div class="col-12 d-grid"><button class="btn btn-outline-primary" type="submit"><?= htmlspecialchars((string) ($tr['add_url_btn'] ?? 'Add URL'), ENT_QUOTES, 'UTF-8') ?></button></div>
      </form>
    </div>
  </div>
</div></section>

<section class="card mb-3"><div class="card-body">
  <h2 class="h5 mb-3"><?= htmlspecialchars((string) ($tr['explorer'] ?? 'Media explorer'), ENT_QUOTES, 'UTF-8') ?></h2>
  <?php if ($assets === []): ?>
    <p class="text-body-secondary mb-0"><?= htmlspecialchars((string) ($tr['no_media'] ?? 'No media found'), ENT_QUOTES, 'UTF-8') ?></p>
  <?php else: ?>
    <div class="d-flex flex-wrap gap-2 mb-3">
      <button type="button" class="btn btn-sm btn-outline-primary cat-media-filter-btn active" data-media-filter="all"><?= htmlspecialchars((string) ($tr['filter_all'] ?? 'Tous'), ENT_QUOTES, 'UTF-8') ?></button>
      <button type="button" class="btn btn-sm btn-outline-primary cat-media-filter-btn" data-media-filter="image"><?= htmlspecialchars((string) ($tr['filter_images'] ?? 'Images'), ENT_QUOTES, 'UTF-8') ?></button>
      <button type="button" class="btn btn-sm btn-outline-primary cat-media-filter-btn" data-media-filter="video"><?= htmlspecialchars((string) ($tr['filter_videos'] ?? 'Vidéos'), ENT_QUOTES, 'UTF-8') ?></button>
    </div>
    <div class="d-flex flex-wrap gap-3">
      <?php foreach ($assets as $asset): ?>
        <?php
          $url = trim((string) ($asset['public_url'] ?? ''));
          $id = (int) ($asset['id'] ?? 0);
          $type = strtolower(trim((string) ($asset['media_type'] ?? 'image')));
        ?>
        <article class="card" style="width:220px;" data-media-id="<?= $id ?>" data-media-type="<?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>" data-media-url="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>">
          <div class="ratio ratio-16x9 bg-body-tertiary">
            <?php if ($type === 'video'): ?>
              <video src="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>" class="w-100 h-100 object-fit-cover" muted preload="metadata"></video>
            <?php else: ?>
              <img src="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>" alt="" class="w-100 h-100 object-fit-cover">
            <?php endif; ?>
          </div>
          <div class="card-body p-2">
            <div class="small fw-semibold">#<?= $id ?> · <?= htmlspecialchars(strtoupper($type), ENT_QUOTES, 'UTF-8') ?></div>
            <div class="small text-body-secondary text-truncate"><?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?></div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div></section>

<section class="card mb-3"><div class="card-body">
  <h2 class="h5 mb-3"><?= htmlspecialchars((string) ($tr['entity_panel'] ?? 'Entity linkage'), ENT_QUOTES, 'UTF-8') ?></h2>
  <form method="post" action="<?= htmlspecialchars($adminBase . '/modules/media-link/sync', ENT_QUOTES, 'UTF-8') ?>" class="row g-2" id="media-link-sync-form">
    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
    <div class="col-12 col-md-3"><label class="form-label"><?= htmlspecialchars((string) ($tr['entity_type'] ?? 'Entity type'), ENT_QUOTES, 'UTF-8') ?></label><input class="form-control" name="entity_type" value="<?= htmlspecialchars($entityType, ENT_QUOTES, 'UTF-8') ?>" required></div>
    <div class="col-12 col-md-2"><label class="form-label"><?= htmlspecialchars((string) ($tr['entity_id'] ?? 'Entity ID'), ENT_QUOTES, 'UTF-8') ?></label><input class="form-control" type="number" name="entity_id" min="1" value="<?= $entityId ?>" required></div>
    <div class="col-12 col-md-2"><label class="form-label"><?= htmlspecialchars((string) ($tr['featured_media'] ?? 'Featured media'), ENT_QUOTES, 'UTF-8') ?></label><input class="form-control" name="featured_media_id" id="featured-media-id" type="number" min="0" value="<?= (int) (($preview['featured']['media_id'] ?? 0)) ?>"></div>
    <div class="col-12 col-md-3"><label class="form-label"><?= htmlspecialchars((string) ($tr['gallery_media'] ?? 'Gallery IDs'), ENT_QUOTES, 'UTF-8') ?></label><input class="form-control" name="gallery_media_ids" id="gallery-media-ids" placeholder="12,34,90"></div>
    <div class="col-12 col-md-2"><label class="form-label"><?= htmlspecialchars((string) ($tr['social_media'] ?? 'Social media'), ENT_QUOTES, 'UTF-8') ?></label><input class="form-control" name="social_media_id" id="social-media-id" type="number" min="0"></div>
    <div class="col-12 d-flex justify-content-end"><button class="btn btn-primary" type="submit"><?= htmlspecialchars((string) ($tr['sync'] ?? 'Sync links'), ENT_QUOTES, 'UTF-8') ?></button></div>
  </form>
</div></section>

<section class="card mb-3"><div class="card-body">
  <h2 class="h5 mb-3"><?= htmlspecialchars((string) ($tr['usage_title'] ?? 'Recent usages'), ENT_QUOTES, 'UTF-8') ?></h2>
  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead><tr><th><?= htmlspecialchars((string) ($tr['usage_entity'] ?? 'Entity'), ENT_QUOTES, 'UTF-8') ?></th><th><?= htmlspecialchars((string) ($tr['usage_type'] ?? 'Type'), ENT_QUOTES, 'UTF-8') ?></th><th><?= htmlspecialchars((string) ($tr['usage_media'] ?? 'Media'), ENT_QUOTES, 'UTF-8') ?></th><th><?= htmlspecialchars((string) ($tr['usage_primary'] ?? 'Primary'), ENT_QUOTES, 'UTF-8') ?></th></tr></thead>
      <tbody>
      <?php if ($usages === []): ?>
        <tr><td colspan="4" class="text-center py-4 text-body-secondary"><?= htmlspecialchars((string) ($tr['usage_empty'] ?? 'No usage recorded'), ENT_QUOTES, 'UTF-8') ?></td></tr>
      <?php else: ?>
        <?php foreach ($usages as $row): ?>
          <tr>
            <td><?= htmlspecialchars((string) ($row['entity_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?>:<?= (int) ($row['entity_id'] ?? 0) ?></td>
            <td><span class="badge text-bg-secondary"><?= htmlspecialchars((string) ($row['link_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></td>
            <td>#<?= (int) ($row['media_id'] ?? 0) ?></td>
            <td><span class="badge <?= (int) ($row['is_primary'] ?? 0) === 1 ? 'text-bg-success' : 'text-bg-light border' ?>"><?= htmlspecialchars((string) ((int) ($row['is_primary'] ?? 0) === 1 ? ($tr['yes'] ?? 'Yes') : ($tr['no'] ?? 'No')), ENT_QUOTES, 'UTF-8') ?></span></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div></section>

<section class="card"><div class="card-body">
  <h2 class="h5 mb-2"><?= htmlspecialchars((string) ($tr['code_title'] ?? 'Snippet'), ENT_QUOTES, 'UTF-8') ?></h2>
  <p class="text-body-secondary"><?= htmlspecialchars((string) ($tr['code_help'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
  <pre class="mb-0"><code>&lt;?php
$entityType = 'page';
$entityId = 42;
// Exemple SQL direct (bridge) :
SELECT * FROM mod_cat_media_link_links
WHERE entity_type = :entity_type AND entity_id = :entity_id
ORDER BY sort_order ASC;
?&gt;</code></pre>
</div></section>
<?php
$content = (string) ob_get_clean();

ob_start();
?>
<script src="<?= htmlspecialchars($adminBase . '/modules/media-link/assets/admin.js?v=1', ENT_QUOTES, 'UTF-8') ?>"></script>
<?php
$scripts = (string) ob_get_clean();
require CATMIN_ADMIN . '/views/layouts/admin.php';
