<?php

declare(strict_types=1);

$flags = isset($flags) && is_array($flags) ? $flags : [];
$missing = array_values(array_filter($flags, static fn (array $row): bool => (($row['type'] ?? '') === 'missing')));
if ($missing === []) {
    return;
}
?>
<div class="alert alert-warning py-2 px-3 mb-2">
  <strong>SEO:</strong> missing fields: <?= htmlspecialchars(implode(', ', array_map(static fn (array $row): string => (string) ($row['field'] ?? ''), $missing)), ENT_QUOTES, 'UTF-8') ?>
</div>
