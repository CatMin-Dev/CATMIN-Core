<?php

declare(strict_types=1);

namespace Install;

final class InstallerLockManager
{
    public function isLocked(): bool
    {
        return is_file($this->lockFile());
    }

    public function lock(array $payload): void
    {
        $dir = dirname($this->lockFile());
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $record = [
            'locked_at' => date('c'),
            'payload' => $payload,
        ];

        file_put_contents($this->lockFile(), json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);

        $note = CATMIN_STORAGE . '/install/neutralization-note.txt';
        file_put_contents($note, "Installer locked. You can now remove the /install directory from deployment if desired.\n", LOCK_EX);
    }

    public function lockFile(): string
    {
        return CATMIN_STORAGE . '/install/installed.lock';
    }
}
