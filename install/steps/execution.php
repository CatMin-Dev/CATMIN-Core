<?php

declare(strict_types=1);

return [
    'title' => 'Execution',
    'validate' => static fn (array $input = [], \Install\InstallerContext|null $context = null): array => ['ok' => true, 'data' => []],
];
