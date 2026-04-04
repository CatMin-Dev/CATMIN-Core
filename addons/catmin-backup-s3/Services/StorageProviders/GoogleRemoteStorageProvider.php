<?php

namespace Addons\CatminBackupS3\Services\StorageProviders;

use Google\Cloud\Storage\StorageClient;
use League\Flysystem\Filesystem;
use League\Flysystem\GoogleCloudStorage\GoogleCloudStorageAdapter;

class GoogleRemoteStorageProvider extends AbstractFlysystemProvider
{
    /** @param array<string, mixed> $settings */
    public function __construct(array $settings)
    {
        $keyJson = (string) ($settings['google_service_account_json'] ?? '');
        $decoded = json_decode($keyJson, true);

        if (!is_array($decoded) || $decoded === []) {
            throw new \InvalidArgumentException('Google service account JSON invalide.');
        }

        $client = new StorageClient([
            'projectId' => (string) ($settings['google_project_id'] ?? ''),
            'keyFile' => $decoded,
        ]);

        $bucket = $client->bucket((string) ($settings['google_bucket'] ?? ''));
        $adapter = new GoogleCloudStorageAdapter($bucket);
        $this->filesystem = new Filesystem($adapter);
    }

    public function sourceLabel(): string
    {
        return 'google';
    }
}
