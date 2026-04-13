<?php

declare(strict_types=1);

$tags = isset($tags) && is_array($tags) ? $tags : [];
$names = array_values(array_filter(array_map(static fn (array $t): string => trim((string) ($t['name'] ?? '')), $tags), static fn (string $v): bool => $v !== ''));
echo htmlspecialchars(implode(', ', $names), ENT_QUOTES, 'UTF-8');
