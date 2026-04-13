<?php

declare(strict_types=1);

$active = !empty($active);
?>
<span class="badge <?= $active ? 'text-bg-success' : 'text-bg-warning' ?>">social image <?= $active ? 'ok' : 'missing' ?></span>
