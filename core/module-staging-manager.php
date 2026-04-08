<?php

declare(strict_types=1);

final class CoreModuleStagingManager
{
    public function incomingDir(): string
    {
        return CATMIN_STORAGE . '/modules/incoming';
    }

    public function stagingDir(): string
    {
        return CATMIN_STORAGE . '/modules/staging';
    }

    public function rejectedDir(): string
    {
        return CATMIN_STORAGE . '/modules/rejected';
    }

    public function installLogsDir(): string
    {
        return CATMIN_STORAGE . '/modules/install-logs';
    }

    public function ensure(): void
    {
        foreach ([$this->incomingDir(), $this->stagingDir(), $this->rejectedDir(), $this->installLogsDir()] as $dir) {
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
        }
    }
}

