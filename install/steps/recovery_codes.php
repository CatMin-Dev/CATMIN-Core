<?php

declare(strict_types=1);

return [
    'title' => 'Recovery Codes',
    'validate' => static fn (array $input = [], \Install\InstallerContext|null $context = null): array => ['ok' => true, 'data' => []],
];
