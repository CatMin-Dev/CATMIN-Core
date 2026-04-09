<?php

declare(strict_types=1);

require_once CATMIN_CORE . '/keyring-manager.php';
require_once CATMIN_CORE . '/trusted-publisher-registry.php';
require_once CATMIN_CORE . '/keyring-cache.php';
require_once CATMIN_CORE . '/trust-scope-resolver.php';

final class CoreTrustCenter
{
    public function __construct(
        private readonly CoreKeyringManager $manager = new CoreKeyringManager(),
        private readonly CoreTrustedPublisherRegistry $publishers = new CoreTrustedPublisherRegistry(),
        private readonly CoreKeyringCache $cache = new CoreKeyringCache(),
        private readonly CoreTrustScopeResolver $resolver = new CoreTrustScopeResolver(),
    ) {}

    public function snapshot(): array
    {
        $keys = $this->manager->all();
        $groups = [
            'official' => [],
            'trusted' => [],
            'community' => [],
            'local_only' => [],
            'revoked' => [],
        ];

        foreach ($keys as $entry) {
            $scope = $this->resolver->normalize((string) ($entry['scope'] ?? 'community'));
            $groups[$scope][] = $entry;
        }

        $cacheState = $this->cache->loadKeyringCache();
        $remoteConfig = (array) config('keyring.remote', []);
        $syncEnabled = (bool) ($remoteConfig['enabled'] ?? false) && (bool) config('trust-policy.allow_remote_sync', false);
        $mode = (string) config('trust-policy.mode', 'local_only');
        $policy = (array) config('trust-policy', []);

        $sources = [
            [
                'name' => 'embedded',
                'label' => 'Keyring embarqué',
                'status' => 'ok',
                'details' => 'Trust anchor local actif',
            ],
            [
                'name' => 'local_cache',
                'label' => 'Cache local',
                'status' => $this->cache->ensureStorage() ? 'ok' : 'warning',
                'details' => $this->cache->trustDir(),
            ],
            [
                'name' => 'manual_import',
                'label' => 'Import manuel officiel',
                'status' => (string) ($cacheState['last_import_status'] ?? 'never'),
                'details' => (string) (($cacheState['last_import_message'] ?? '') !== '' ? $cacheState['last_import_message'] : 'Aucun import manuel'),
            ],
            [
                'name' => 'remote_registry',
                'label' => 'Registry distant',
                'status' => $syncEnabled ? ((string) ($cacheState['last_sync_status'] ?? 'idle')) : 'disabled',
                'details' => $syncEnabled
                    ? ('keyring=' . (string) ($remoteConfig['keyring_url'] ?? '') . ' | registry=' . (string) ($remoteConfig['registry_url'] ?? ''))
                    : 'Registry distant non configuré (fallback local actif)',
            ],
        ];

        return [
            'mode' => $mode,
            'policy' => $policy,
            'remote' => $remoteConfig,
            'sync_enabled' => $syncEnabled,
            'last_sync_at' => (string) ($cacheState['last_sync_at'] ?? ''),
            'last_sync_status' => (string) ($cacheState['last_sync_status'] ?? 'disabled'),
            'last_sync_message' => (string) ($cacheState['last_sync_message'] ?? 'registry distant non configure'),
            'last_import_at' => (string) ($cacheState['last_import_at'] ?? ''),
            'last_import_status' => (string) ($cacheState['last_import_status'] ?? 'never'),
            'last_import_message' => (string) ($cacheState['last_import_message'] ?? ''),
            'keys' => $keys,
            'groups' => $groups,
            'stats' => [
                'total' => count($keys),
                'official' => count($groups['official']),
                'trusted' => count($groups['trusted']),
                'community' => count($groups['community']),
                'local_only' => count($groups['local_only']),
                'revoked' => count($groups['revoked']),
            ],
            'sources' => $sources,
            'publishers' => $this->publishers->all(),
            'files' => [
                'keyring_cache' => $this->cache->keyringCacheFile(),
                'registry_cache' => $this->cache->registryCacheFile(),
            ],
        ];
    }

    public function addLocalKey(array $payload): array
    {
        return $this->manager->addLocalKey($payload);
    }

    public function removeLocalKey(string $keyId): array
    {
        return $this->manager->removeLocalKey($keyId);
    }

    public function syncRemote(): array
    {
        return $this->manager->syncRemote();
    }

    public function importOfficialKeyringFromJson(string $rawJson): array
    {
        return $this->manager->importOfficialKeyringFromJson($rawJson);
    }

    public function revokeKey(string $keyId, string $reason = ''): array
    {
        return $this->manager->revokeKey($keyId, $reason);
    }
}
