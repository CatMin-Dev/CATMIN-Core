<?php

declare(strict_types=1);

$record = isset($record) && is_array($record) ? $record : [];
?>
<div class="card"><div class="card-body">
  <h3 class="h6 mb-3">SEO</h3>
  <div class="row g-2">
    <div class="col-12"><label class="form-label">SEO title</label><input class="form-control" name="seo_title" value="<?= htmlspecialchars((string) ($record['seo_title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
    <div class="col-12"><label class="form-label">Meta description</label><textarea class="form-control" name="meta_description" rows="3"><?= htmlspecialchars((string) ($record['meta_description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea></div>
    <div class="col-12 col-md-6"><label class="form-label">Canonical URL</label><input class="form-control" name="canonical_url" value="<?= htmlspecialchars((string) ($record['canonical_url'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
    <div class="col-12 col-md-6"><label class="form-label">Focus keyword</label><input class="form-control" name="focus_keyword" value="<?= htmlspecialchars((string) ($record['focus_keyword'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
  </div>
</div></div>
