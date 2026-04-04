<?php

namespace Tests\Unit\RemoteBackup;

use Addons\CatminBackupS3\Services\RemoteBackupService;
use Addons\CatminBackupS3\Services\RemoteBackupSettingsService;
use Addons\CatminBackupS3\Services\RemoteStorageProviderFactory;
use Addons\CatminBackupS3\Services\StorageProviders\RemoteStorageProviderInterface;
use Mockery;
use Tests\TestCase;

class RemoteBackupServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_list_local_backups_reads_existing_directories(): void
    {
        $base = storage_path('app/backups/test-local-' . uniqid());
        @mkdir($base, 0755, true);
        file_put_contents($base . '/manifest.json', json_encode([
            'format' => 'catmin.backup.v1',
            'created_at' => now()->toIso8601String(),
        ], JSON_PRETTY_PRINT));

        $settings = Mockery::mock(RemoteBackupSettingsService::class);
        $factory = Mockery::mock(RemoteStorageProviderFactory::class);

        $service = new RemoteBackupService($settings, $factory);
        $list = $service->listLocalBackups();

        $this->assertIsArray($list);
        $this->assertTrue(collect($list)->contains(fn (array $row) => ($row['name'] ?? '') === basename($base)));
    }

    public function test_apply_retention_deletes_oldest_files(): void
    {
        $provider = Mockery::mock(RemoteStorageProviderInterface::class);
        $provider->shouldReceive('list')->once()->andReturn([
            ['path' => 'a.zip', 'size' => 1, 'last_modified' => 100, 'type' => 'zip', 'source' => 's3'],
            ['path' => 'b.zip', 'size' => 1, 'last_modified' => 200, 'type' => 'zip', 'source' => 's3'],
            ['path' => 'c.zip', 'size' => 1, 'last_modified' => 300, 'type' => 'zip', 'source' => 's3'],
        ]);
        $provider->shouldReceive('delete')->once()->with('a.zip');

        $settings = Mockery::mock(RemoteBackupSettingsService::class);
        $settings->shouldReceive('all')->andReturn([
            'provider' => 's3',
            'retention_max' => 2,
            'prefix' => 'catmin/backups',
        ]);

        $factory = Mockery::mock(RemoteStorageProviderFactory::class);
        $factory->shouldReceive('make')->andReturn($provider);

        $service = new RemoteBackupService($settings, $factory);
        $result = $service->applyRetention();

        $this->assertTrue($result['ok']);
        $this->assertSame(1, $result['deleted']);
    }

    public function test_test_connection_uses_provider(): void
    {
        $provider = Mockery::mock(RemoteStorageProviderInterface::class);
        $provider->shouldReceive('testConnection')->once()->andReturn(true);

        $settings = Mockery::mock(RemoteBackupSettingsService::class);
        $settings->shouldReceive('all')->andReturn(['provider' => 'ftp']);

        $factory = Mockery::mock(RemoteStorageProviderFactory::class);
        $factory->shouldReceive('make')->andReturn($provider);

        $service = new RemoteBackupService($settings, $factory);

        $this->assertTrue($service->testConnection());
    }

    public function test_upload_local_backup_calls_provider_upload(): void
    {
        $name = 'test-upload-' . uniqid();
        $dir = storage_path('app/backups/' . $name);
        @mkdir($dir, 0755, true);
        file_put_contents($dir . '/manifest.json', json_encode(['format' => 'catmin.backup.v1']));

        $provider = Mockery::mock(RemoteStorageProviderInterface::class);
        $provider->shouldReceive('upload')->once();

        $settings = Mockery::mock(RemoteBackupSettingsService::class);
        $settings->shouldReceive('all')->andReturn([
            'provider' => 's3',
            'prefix' => 'catmin/backups',
        ]);

        $factory = Mockery::mock(RemoteStorageProviderFactory::class);
        $factory->shouldReceive('make')->andReturn($provider);

        $service = new RemoteBackupService($settings, $factory);
        $result = $service->uploadLocalBackup($name);

        $this->assertTrue($result['ok']);
        $this->assertNotEmpty($result['remote_path'] ?? '');
    }

    public function test_list_remote_backups_uses_provider_listing(): void
    {
        $provider = Mockery::mock(RemoteStorageProviderInterface::class);
        $provider->shouldReceive('list')->once()->with('catmin/backups')->andReturn([
            ['path' => 'catmin/backups/sample.zip', 'size' => 123, 'last_modified' => 1000, 'type' => 'zip', 'source' => 'ftp'],
        ]);

        $settings = Mockery::mock(RemoteBackupSettingsService::class);
        $settings->shouldReceive('all')->andReturn([
            'provider' => 'ftp',
            'prefix' => 'catmin/backups',
        ]);

        $factory = Mockery::mock(RemoteStorageProviderFactory::class);
        $factory->shouldReceive('make')->andReturn($provider);

        $service = new RemoteBackupService($settings, $factory);
        $rows = $service->listRemoteBackups();

        $this->assertCount(1, $rows);
        $this->assertSame('catmin/backups/sample.zip', $rows[0]['path']);
    }
}
