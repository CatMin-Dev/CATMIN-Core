<?php

declare(strict_types=1);

return [
    'title' => 'Precheck',
    'validate' => static function (array $input, \Install\InstallerContext|null $context = null): array {
        $precheck = (new \Install\InstallerPrecheck())->run();
        // Etape informative: on persiste le rapport et on autorise la suite du wizard.
        return ['ok' => true, 'data' => $precheck];
    },
];
