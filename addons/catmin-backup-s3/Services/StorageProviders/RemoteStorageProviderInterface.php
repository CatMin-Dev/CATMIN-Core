<?php

namespace Addons\CatminBackupS3\Services\StorageProviders;

interface RemoteStorageProviderInterface
{
    /**
     * @param string $localPath Full local path.
     * @param string $remotePath Remote object path.
     */
    public function upload(string $localPath, string $remotePath): void;

    /**
     * @return array<int, array{path:string,size:int,last_modified:int,type:string,source:string}>
     */
    public function list(string $prefix = ''): array;

    /**
     * @param string $remotePath Remote object path.
     * @param string $localPath Full local path.
     */
    public function download(string $remotePath, string $localPath): void;

    public function delete(string $remotePath): void;

    public function testConnection(): bool;

    public function sourceLabel(): string;
}
