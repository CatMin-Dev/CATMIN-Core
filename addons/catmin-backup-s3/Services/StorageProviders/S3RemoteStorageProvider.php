<?php

namespace Addons\CatminBackupS3\Services\StorageProviders;

use Aws\S3\S3Client;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\Filesystem;

class S3RemoteStorageProvider extends AbstractFlysystemProvider
{
    /** @param array<string, mixed> $settings */
    public function __construct(array $settings)
    {
        $client = new S3Client([
            'version' => 'latest',
            'region' => (string) ($settings['region'] ?? 'eu-west-1'),
            'endpoint' => (string) ($settings['endpoint'] ?? ''),
            'use_path_style_endpoint' => (bool) ($settings['use_path_style_endpoint'] ?? true),
            'credentials' => [
                'key' => (string) ($settings['access_key'] ?? ''),
                'secret' => (string) ($settings['secret_key'] ?? ''),
            ],
        ]);

        $adapter = new AwsS3V3Adapter($client, (string) ($settings['bucket'] ?? ''));
        $this->filesystem = new Filesystem($adapter);
    }

    public function sourceLabel(): string
    {
        return 's3';
    }
}
