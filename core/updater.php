<?php

declare(strict_types=1);

require_once CATMIN_CORE . '/updater-runner.php';

final class CoreUpdater
{
    public function check(): array
    {
        return (new CoreUpdaterRunner())->check();
    }

    public function dryRun(): array
    {
        return (new CoreUpdaterRunner())->run(true);
    }

    public function updateNow(): array
    {
        return (new CoreUpdaterRunner())->run(false);
    }
}
