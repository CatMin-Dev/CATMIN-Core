<?php

declare(strict_types=1);

$selector = isset($selector) && is_array($selector) ? $selector : [];
$selectedIds = isset($selectedIds) && is_array($selectedIds) ? $selectedIds : [];
?>
<div class="card"><div class="card-body">
  <h3 class="h6 mb-3">Categories</h3>
  <div class="row g-2">
    <div class="col-12">
      <select class="form-select" name="category_ids[]" multiple size="6">
      <?php foreach ($selector as $row): ?>
        <?php $id = (int) ($row['id'] ?? 0); ?>
        <option value="<?= $id ?>" <?= in_array($id, $selectedIds, true) ? 'selected' : '' ?>><?= htmlspecialchars((string) ($row['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
      <?php endforeach; ?>
      </select>
    </div>
  </div>
</div></div>
