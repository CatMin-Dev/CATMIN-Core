<?php $activeTheme = (string) ($topbar['active_theme'] ?? 'corporate'); ?>
<div class="dropdown">
    <button type="button" class="cat-icon-btn dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" aria-label="<?= htmlspecialchars(__('topbar.theme'), ENT_QUOTES, 'UTF-8') ?>">
        <i class="bi bi-moon-stars"></i>
    </button>
    <ul class="dropdown-menu dropdown-menu-end">
        <?php foreach ((array) ($topbar['themes'] ?? ['light', 'dark', 'corporate']) as $theme): ?>
            <?php
            $theme = (string) $theme;
            $label = ucfirst($theme);
            if ($theme === 'corporate') {
                $label = 'Corporate · CATMIN';
            }
            ?>
            <li>
                <button type="button" class="dropdown-item js-theme-set <?= $activeTheme === $theme ? 'active' : '' ?>" data-theme="<?= htmlspecialchars($theme, ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                </button>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
