<?php

namespace Addons\CatminBackupS3\Services;

use Addons\CatminBackupS3\Services\StorageProviders\FtpRemoteStorageProvider;
use Addons\CatminBackupS3\Services\StorageProviders\GoogleRemoteStorageProvider;
use Addons\CatminBackupS3\Services\StorageProviders\RemoteStorageProviderInterface;
use Addons\CatminBackupS3\Services\StorageProviders\S3RemoteStorageProvider;
use Addons\CatminBackupS3\Services\StorageProviders\SftpRemoteStorageProvider;

class RemoteStorageProviderFactory
{
    /** @param array<string, mixed> $settings */
    public function make(array $settings): RemoteStorageProviderInterface
    {
        $provider = strtolower((string) ($settings['provider'] ?? 's3'));

        return match ($provider) {
            's3' => new S3RemoteStorageProvider($settings),
            'google' => new GoogleRemoteStorageProvider($settings),
            'sftp' => new SftpRemoteStorageProvider($settings),
            'ftp' => new FtpRemoteStorageProvider($settings),
            default => throw new \InvalidArgumentException('Provider remote non supporte: ' . $provider),
        };
    }
}
