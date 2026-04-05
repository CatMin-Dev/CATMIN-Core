<?php

declare(strict_types=1);

return [
    'title' => 'Lock',
    'validate' => static function (array $input, \Install\InstallerContext|null $context = null): array {
        $confirm = ($input['confirm_lock'] ?? '') === '1';

        return $confirm
            ? ['ok' => true, 'data' => ['confirm_lock' => true]]
            : ['ok' => false, 'message' => 'You must confirm final lock.', 'data' => []];
    },
];
