<?php
$search = is_array($topbar['search'] ?? null) ? $topbar['search'] : [];
$searchPlaceholder = (string) ($search['placeholder'] ?? __('topbar.search_placeholder'));
$searchButton = (string) ($search['button'] ?? __('topbar.search_button'));
?>
<form class="cat-search-form" role="search" data-cat-search-form>
    <span class="cat-search-icon"><i class="bi bi-search" aria-hidden="true"></i></span>
    <input type="search" class="form-control cat-topbar-search" placeholder="<?= htmlspecialchars($searchPlaceholder, ENT_QUOTES, 'UTF-8') ?>">
    <button type="button" class="btn btn-primary btn-sm cat-search-submit"><?= htmlspecialchars($searchButton, ENT_QUOTES, 'UTF-8') ?></button>
</form>
