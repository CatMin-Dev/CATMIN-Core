<?php

declare(strict_types=1);

$score = max(0, min(100, (int) ($score ?? 0)));
$widthClass = $score >= 90 ? 'w-100' : ($score >= 75 ? 'w-75' : ($score >= 50 ? 'w-50' : ($score >= 25 ? 'w-25' : '')));
?>
<div class="progress" role="progressbar" aria-label="SEO score" aria-valuenow="<?= $score ?>" aria-valuemin="0" aria-valuemax="100">
  <div class="progress-bar <?= $widthClass ?>"></div>
</div>
<p class="small text-body-secondary mt-1 mb-0">SEO score: <?= $score ?>/100</p>
