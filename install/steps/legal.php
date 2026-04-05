<?php

declare(strict_types=1);

return [
    'title' => 'Legal',
    'validate' => static function (array $input, \Install\InstallerContext|null $context = null): array {
        $accepted = ($input['accept_legal'] ?? '') === '1';

        return $accepted
            ? ['ok' => true, 'data' => ['accept_legal' => true]]
            : ['ok' => false, 'message' => 'You must accept legal documents.', 'data' => []];
    },
];
