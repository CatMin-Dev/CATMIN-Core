<?php

namespace Addons\CatminBackupS3\Services\StorageProviders;

use League\Flysystem\Filesystem;
use League\Flysystem\Ftp\FtpAdapter;
use League\Flysystem\Ftp\FtpConnectionOptions;

class FtpRemoteStorageProvider extends AbstractFlysystemProvider
{
    /** @param array<string, mixed> $settings */
    public function __construct(array $settings)
    {
        $options = FtpConnectionOptions::fromArray([
            'host' => (string) ($settings['host'] ?? ''),
            'root' => (string) ($settings['root'] ?? '/'),
            'username' => (string) ($settings['username'] ?? ''),
            'password' => (string) ($settings['password'] ?? ''),
            'port' => (int) ($settings['port'] ?? 21),
            'ssl' => (bool) ($settings['ssl'] ?? false),
            'timeout' => (int) ($settings['timeout'] ?? 30),
            'passive' => (bool) ($settings['passive'] ?? true),
        ]);

        $adapter = new FtpAdapter($options);
        $this->filesystem = new Filesystem($adapter);
    }

    public function sourceLabel(): string
    {
        return 'ftp';
    }
}
