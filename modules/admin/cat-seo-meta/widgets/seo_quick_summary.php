<?php

declare(strict_types=1);

$score = max(0, min(100, (int) ($score ?? 0)));
$summary = trim((string) ($summary ?? ''));
if ($summary === '') {
    if ($score >= 80) {
        $summary = 'Strong SEO baseline.';
    } elseif ($score >= 60) {
        $summary = 'SEO acceptable with room for optimization.';
    } else {
        $summary = 'SEO baseline is weak, complete core metadata.';
    }
}
?>
<p class="small mb-0"><?= htmlspecialchars($summary, ENT_QUOTES, 'UTF-8') ?></p>
