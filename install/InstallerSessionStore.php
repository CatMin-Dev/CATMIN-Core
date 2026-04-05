<?php

declare(strict_types=1);

namespace Install;

final class InstallerSessionStore
{
    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name('CATMIN_INSTALL_SESSID');
            session_start();
        }
    }

    public function load(): InstallerContext
    {
        $path = $this->path();
        if (!is_file($path)) {
            return new InstallerContext();
        }

        $raw = file_get_contents($path);
        if (!is_string($raw) || trim($raw) === '') {
            return new InstallerContext();
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return new InstallerContext();
        }

        return InstallerContext::fromArray($decoded);
    }

    public function save(InstallerContext $context): void
    {
        $path = $this->path();
        $dir = dirname($path);

        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        file_put_contents($path, json_encode($context->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    }

    public function clear(): void
    {
        $path = $this->path();
        if (is_file($path)) {
            unlink($path);
        }
    }

    private function path(): string
    {
        $sessionId = session_id();
        if ($sessionId === '') {
            $sessionId = 'install';
        }

        return CATMIN_STORAGE . '/install/sessions/' . $sessionId . '.json';
    }
}
