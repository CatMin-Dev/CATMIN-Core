<?php

declare(strict_types=1);

$sections = isset($sections) && is_array($sections) ? $sections : [];
$activeSection = strtolower(trim((string) ($activeSection ?? '')));
$settingsModuleLinks = isset($settingsModuleLinks) && is_array($settingsModuleLinks) ? $settingsModuleLinks : [];
$activeModuleHref = trim((string) ($activeModuleHref ?? ''));

?>
<div class="list-group cat-settings-nav">
    <?php foreach ($sections as $key => $label): ?>
        <?php $href = $adminBase . '/settings/' . $key; ?>
        <a class="list-group-item list-group-item-action <?= $activeSection === (string) $key ? 'active' : '' ?>" href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>">
            <?= htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8') ?>
        </a>
    <?php endforeach; ?>

    <?php foreach ($settingsModuleLinks as $entry): ?>
        <?php
        $href = trim((string) ($entry['href'] ?? ''));
        if ($href === '') {
            continue;
        }
        if ($href[0] !== '/') {
            $href = rtrim($adminBase, '/') . '/' . ltrim($href, '/');
        }

        $label = trim((string) ($entry['label'] ?? ''));
        if ($label === '') {
            continue;
        }

        $isActive = $activeModuleHref !== '' && $href === $activeModuleHref;
        ?>
        <a class="list-group-item list-group-item-action <?= $isActive ? 'active' : '' ?>" href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>">
            <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
        </a>
    <?php endforeach; ?>
</div>