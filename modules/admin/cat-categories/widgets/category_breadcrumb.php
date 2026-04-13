<?php

declare(strict_types=1);

$trail = isset($trail) && is_array($trail) ? $trail : [];
$names = array_values(array_filter(array_map(static fn (array $row): string => trim((string) ($row['name'] ?? '')), $trail), static fn (string $v): bool => $v !== ''));
echo htmlspecialchars(implode(' > ', $names), ENT_QUOTES, 'UTF-8');
