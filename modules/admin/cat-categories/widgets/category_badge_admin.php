<?php

declare(strict_types=1);

$name = trim((string) ($name ?? ''));
if ($name === '') {
    return;
}
?><span class="badge text-bg-secondary"><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></span>
