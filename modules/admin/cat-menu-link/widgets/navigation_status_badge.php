<?php

declare(strict_types=1);

$enabled = !empty($enabled);
?>
<span class="badge <?= $enabled ? 'text-bg-success' : 'text-bg-warning' ?>"><?= $enabled ? 'navigation:on' : 'navigation:off' ?></span>
