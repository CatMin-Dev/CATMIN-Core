<?php
$search = is_array($topbar['search'] ?? null) ? $topbar['search'] : [];
$searchPlaceholder = (string) ($search['placeholder'] ?? __('topbar.search_placeholder'));
$searchButton = (string) ($search['button'] ?? __('topbar.search_button'));
$searchItems = array_values(array_filter((array) ($search['items'] ?? []), static fn (mixed $item): bool => is_array($item)));
$adminBase = isset($adminBase) ? (string) $adminBase : '/admin';
?>
<form
    class="cat-search-form"
    role="search"
    data-cat-search-form
    data-cat-search-items="<?= htmlspecialchars((string) json_encode($searchItems, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?>"
    method="get"
    action="<?= htmlspecialchars($adminBase . '/', ENT_QUOTES, 'UTF-8') ?>"
    autocomplete="off"
>
    <span class="cat-search-icon"><i class="bi bi-search" aria-hidden="true"></i></span>
    <input type="search" name="q" class="form-control cat-topbar-search" placeholder="<?= htmlspecialchars($searchPlaceholder, ENT_QUOTES, 'UTF-8') ?>" aria-label="<?= htmlspecialchars($searchPlaceholder, ENT_QUOTES, 'UTF-8') ?>" data-cat-search-input>
    <button type="submit" class="btn btn-primary btn-sm cat-search-submit"><?= htmlspecialchars($searchButton, ENT_QUOTES, 'UTF-8') ?></button>
    <div class="cat-search-suggest" data-cat-search-suggest hidden></div>
</form>
