<?php
$language = is_array($topbar['language'] ?? null) ? $topbar['language'] : [];
$activeLocale = strtolower(trim((string) ($language['active'] ?? catmin_locale())));
$options = is_array($language['options'] ?? null) ? $language['options'] : [];

$activeLabel = $activeLocale === 'en' ? 'English' : 'Français';
$activeFlag = $activeLocale === 'en' ? '🇺🇸' : '🇫🇷';
$next = (string) ($_SERVER['REQUEST_URI'] ?? ($adminBase . '/'));
if (!str_starts_with($next, $adminBase)) {
    $next = $adminBase . '/';
}
foreach ($options as $option) {
    if (strtolower((string) ($option['code'] ?? '')) !== $activeLocale) {
        continue;
    }
    $activeLabel = (string) ($option['label'] ?? $activeLabel);
    $activeFlag = (string) ($option['flag'] ?? $activeFlag);
    break;
}
?>
<div class="dropdown">
    <button class="cat-topbar-link dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="<?= htmlspecialchars(__('topbar.language'), ENT_QUOTES, 'UTF-8') ?>">
        <span aria-hidden="true"><?= htmlspecialchars($activeFlag, ENT_QUOTES, 'UTF-8') ?></span> <?= htmlspecialchars($activeLabel, ENT_QUOTES, 'UTF-8') ?>
    </button>
    <ul class="dropdown-menu dropdown-menu-end">
        <?php foreach ($options as $option): ?>
            <?php
            $code = strtolower(trim((string) ($option['code'] ?? 'fr')));
            $label = (string) ($option['label'] ?? strtoupper($code));
            $flag = (string) ($option['flag'] ?? '🌐');
            ?>
            <li>
                <a class="dropdown-item <?= $code === $activeLocale ? 'active' : '' ?>" href="<?= htmlspecialchars($adminBase . '/locale/' . rawurlencode($code) . '?next=' . rawurlencode($next), ENT_QUOTES, 'UTF-8') ?>">
                    <span aria-hidden="true"><?= htmlspecialchars($flag, ENT_QUOTES, 'UTF-8') ?></span>
                    <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
