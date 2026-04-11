<?php
$search = is_array($topbar['search'] ?? null) ? $topbar['search'] : [];
$searchPlaceholder = (string) ($search['placeholder'] ?? __('topbar.search_placeholder'));
$searchButton = (string) ($search['button'] ?? __('topbar.search_button'));
$adminBase = isset($adminBase) ? (string) $adminBase : '/admin';
?>
<form class="cat-search-form" role="search" data-cat-search-form method="get" action="<?= htmlspecialchars($adminBase . '/', ENT_QUOTES, 'UTF-8') ?>">
    <span class="cat-search-icon"><i class="bi bi-search" aria-hidden="true"></i></span>
    <input type="search" name="q" class="form-control cat-topbar-search" placeholder="<?= htmlspecialchars($searchPlaceholder, ENT_QUOTES, 'UTF-8') ?>">
    <button type="submit" class="btn btn-primary btn-sm cat-search-submit"><?= htmlspecialchars($searchButton, ENT_QUOTES, 'UTF-8') ?></button>
</form>
