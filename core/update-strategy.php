<?php

declare(strict_types=1);

use Core\update\UpdateRunner;

require_once CATMIN_CORE . '/update/UpdateRunner.php';

final class CoreUpdateStrategy
{
    public function preflight(?string $targetCoreVersion = null): array
    {
        return (new UpdateRunner())->run($targetCoreVersion, true);
    }

    public function upgrade(?string $targetCoreVersion = null): array
    {
        return (new UpdateRunner())->run($targetCoreVersion, false);
    }
}

