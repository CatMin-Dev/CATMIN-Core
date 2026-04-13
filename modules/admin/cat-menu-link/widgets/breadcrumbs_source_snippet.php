<?php

declare(strict_types=1);

$menuKey = isset($menuKey) ? (string) $menuKey : 'main_nav';
?>
<pre class="mb-0"><code><?= htmlspecialchars("SELECT * FROM mod_cat_menu_link_items WHERE menu_key='" . $menuKey . "' ORDER BY sort_order ASC", ENT_QUOTES, 'UTF-8') ?></code></pre>
