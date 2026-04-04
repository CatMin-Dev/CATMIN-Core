<?php

namespace Addons\CatminBackupS3\Services\StorageProviders;

use League\Flysystem\Filesystem;
use League\Flysystem\PhpseclibV3\SftpAdapter;
use League\Flysystem\PhpseclibV3\SftpConnectionProvider;

class SftpRemoteStorageProvider extends AbstractFlysystemProvider
{
    /** @param array<string, mixed> $settings */
    public function __construct(array $settings)
    {
        $connectionProvider = new SftpConnectionProvider(
            (string) ($settings['host'] ?? ''),
            (string) ($settings['username'] ?? ''),
            (string) ($settings['password'] ?? ''),
            (string) ($settings['private_key'] ?? ''),
            null,
            (int) ($settings['port'] ?? 22),
            false,
            (int) ($settings['timeout'] ?? 30),
            5,
            null,
            null
        );

        $adapter = new SftpAdapter($connectionProvider, (string) ($settings['root'] ?? '/'));
        $this->filesystem = new Filesystem($adapter);
    }

    public function sourceLabel(): string
    {
        return 'sftp';
    }
}
