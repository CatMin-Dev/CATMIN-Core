<?php

declare(strict_types=1);

$score = max(0, min(100, (int) ($score ?? 0)));
$tone = $score >= 80 ? 'success' : ($score >= 60 ? 'warning' : 'danger');
?>
<span class="badge text-bg-<?= htmlspecialchars($tone, ENT_QUOTES, 'UTF-8') ?>">SEO <?= $score ?>/100</span>
