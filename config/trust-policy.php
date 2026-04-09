<?php

declare(strict_types=1);

return [
    'mode' => 'local_only',
    'allow_remote_sync' => false,
    'allow_manual_import' => true,
    'allow_local_only_modules' => false,
    'block_revoked_signatures' => true,
    'rotation_grace_days' => 30,
    'cache_ttl_seconds' => 86400,
    'protect_official_anchors' => true,
];
