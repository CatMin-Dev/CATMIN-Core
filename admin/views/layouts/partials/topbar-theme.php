<?php
$activeTheme = strtolower((string) ($topbar['active_theme'] ?? 'corporate'));
$availableThemes = array_values(array_filter(array_map(
    static fn ($v): string => strtolower(trim((string) $v)),
    (array) ($topbar['themes'] ?? ['light', 'dark', 'corporate'])
)));
if ($availableThemes === []) {
    $availableThemes = ['light', 'dark', 'corporate'];
}
if (!in_array($activeTheme, $availableThemes, true)) {
    $activeTheme = 'corporate';
}
$themeLabels = [
    'light' => __('topbar.theme.light'),
    'dark' => __('topbar.theme.dark'),
    'corporate' => __('topbar.theme.corporate'),
];
$activeThemeLabel = (string) ($themeLabels[$activeTheme] ?? ucfirst($activeTheme));
?>
<div class="dropdown">
    <button type="button" class="cat-topbar-link cat-topbar-theme-btn dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="<?= htmlspecialchars(__('topbar.theme'), ENT_QUOTES, 'UTF-8') ?>">
        <i class="bi bi-moon-stars me-1" aria-hidden="true"></i>
        <span class="cat-theme-label js-theme-label"><?= htmlspecialchars($activeThemeLabel, ENT_QUOTES, 'UTF-8') ?></span>
    </button>
    <ul class="dropdown-menu dropdown-menu-end cat-theme-menu">
        <?php foreach ($availableThemes as $theme): ?>
            <?php
            $label = (string) ($themeLabels[$theme] ?? ucfirst($theme));
            ?>
            <li>
                <button type="button" class="dropdown-item js-theme-set <?= $activeTheme === $theme ? 'active' : '' ?>" data-theme="<?= htmlspecialchars($theme, ENT_QUOTES, 'UTF-8') ?>" data-theme-label="<?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                </button>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
