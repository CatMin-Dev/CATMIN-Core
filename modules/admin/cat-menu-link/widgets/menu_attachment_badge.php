<?php

declare(strict_types=1);

$menuKey = isset($menuKey) ? (string) $menuKey : 'main_nav';
?>
<span class="badge text-bg-secondary">menu: <?= htmlspecialchars($menuKey, ENT_QUOTES, 'UTF-8') ?></span>
