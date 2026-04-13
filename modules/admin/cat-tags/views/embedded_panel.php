<?php

declare(strict_types=1);

$tagsCsv = isset($tagsCsv) ? trim((string) $tagsCsv) : '';
$adminBase = isset($adminBase) ? (string) $adminBase : '/admin';
?>
<div class="card"><div class="card-body">
  <h3 class="h6 mb-3">Tags</h3>
  <input type="hidden" name="tags_csv" id="tags-csv-embedded" value="<?= htmlspecialchars($tagsCsv, ENT_QUOTES, 'UTF-8') ?>">
  <div class="border rounded p-2" id="tags-chip-box-embedded" data-tags-input="embedded">
    <div class="d-flex flex-wrap gap-2" id="tags-chips-embedded"></div>
    <input type="text" class="form-control border-0 px-0 mt-2" id="tags-input-embedded" placeholder="Tag, autreTag ..." autocomplete="off">
    <div class="list-group mt-2" id="tags-suggest-embedded"></div>
  </div>
</div></div>
