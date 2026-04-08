<?php

declare(strict_types=1);

final class CoreInstallBackupCleanup
{
    public function purgeExpired(int $olderThanSeconds = 86400): int
    {
        $dir = CATMIN_STORAGE . '/backups/install';
        if (!is_dir($dir)) {
            return 0;
        }

        $olderThanSeconds = max(3600, $olderThanSeconds);
        $deleted = 0;
        foreach (glob($dir . '/*') ?: [] as $path) {
            if (!is_file($path)) {
                continue;
            }
            $mtime = (int) (@filemtime($path) ?: 0);
            if ($mtime <= 0 || (time() - $mtime) < $olderThanSeconds) {
                continue;
            }
            if (@unlink($path)) {
                $deleted++;
            }
        }

        return $deleted;
    }
}
