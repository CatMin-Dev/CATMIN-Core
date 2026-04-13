<?php

declare(strict_types=1);

$selector = isset($selector) && is_array($selector) ? $selector : [];
$selected = isset($selected) && is_array($selected) ? $selected : [];
?>
<select class="form-select" name="category_ids[]" multiple>
<?php foreach ($selector as $row): ?>
  <?php $id = (int) ($row['id'] ?? 0); ?>
  <option value="<?= $id ?>" <?= in_array($id, $selected, true) ? 'selected' : '' ?>><?= htmlspecialchars((string) ($row['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
<?php endforeach; ?>
</select>
