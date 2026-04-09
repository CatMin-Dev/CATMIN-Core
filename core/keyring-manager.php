<?php

declare(strict_types=1);

require_once CATMIN_CORE . '/keyring-cache.php';
require_once CATMIN_CORE . '/keyring-logger.php';
require_once CATMIN_CORE . '/trust-scope-resolver.php';

final class CoreKeyringManager
{
    public function __construct(
        private readonly CoreKeyringCache $cache = new CoreKeyringCache(),
        private readonly CoreTrustScopeResolver $resolver = new CoreTrustScopeResolver(),
        private readonly CoreKeyringLogger $logger = new CoreKeyringLogger(),
    ) {}

    public function all(): array
    {
        $merged = [];

        foreach ($this->embeddedEntries() as $entry) {
            $merged = $this->mergeEntry($merged, $entry);
        }

        foreach ($this->remoteEntries() as $entry) {
            $merged = $this->mergeEntry($merged, $entry);
        }

        foreach ($this->localEntries() as $entry) {
            $merged = $this->mergeEntry($merged, $entry);
        }

        $revoked = $this->revokedKeyIds();
        foreach ($merged as $keyId => $entry) {
            if (in_array($keyId, $revoked, true)) {
                $entry['revoked'] = true;
                $entry['scope'] = 'revoked';
                $entry['editable'] = false;
                $merged[$keyId] = $entry;
            }
        }

        uasort($merged, fn (array $a, array $b): int =>
            ($this->resolver->rank((string) ($b['scope'] ?? 'community')) <=> $this->resolver->rank((string) ($a['scope'] ?? 'community')))
            ?: strcmp((string) ($a['key_id'] ?? ''), (string) ($b['key_id'] ?? ''))
        );

        return array_values($merged);
    }

    public function allPublicMap(): array
    {
        $map = [];
        foreach ($this->all() as $entry) {
            $scope = (string) ($entry['scope'] ?? 'community');
            if (!$this->resolver->isUsable($scope)) {
                continue;
            }

            $keyId = (string) ($entry['key_id'] ?? '');
            $publicKey = (string) ($entry['public_key'] ?? '');
            if ($keyId === '' || $publicKey === '') {
                continue;
            }

            $map[$keyId] = $publicKey;
        }

        return $map;
    }

    public function find(string $keyId): ?array
    {
        $keyId = trim($keyId);
        if ($keyId === '') {
            return null;
        }

        foreach ($this->all() as $entry) {
            if ((string) ($entry['key_id'] ?? '') === $keyId) {
                return $entry;
            }
        }

        return null;
    }

    public function addLocalKey(array $payload): array
    {
        $keyId = strtolower(trim((string) ($payload['key_id'] ?? '')));
        $publisher = strtolower(trim((string) ($payload['publisher'] ?? 'local-admin')));
        $publicKey = trim((string) ($payload['public_key'] ?? ''));

        if ($keyId === '' || preg_match('/^[a-z0-9._-]{3,120}$/', $keyId) !== 1) {
            return ['ok' => false, 'message' => 'key_id invalide.'];
        }

        if ($publicKey === '' || !str_contains($publicKey, 'BEGIN PUBLIC KEY') || !str_contains($publicKey, 'END PUBLIC KEY')) {
            return ['ok' => false, 'message' => 'Clé publique invalide (format PEM requis).'];
        }

        $existing = $this->find($keyId);
        if (is_array($existing) && !$this->resolver->isEditable((string) ($existing['scope'] ?? ''))) {
            return ['ok' => false, 'message' => 'Cette clé est protégée par son scope de confiance.'];
        }

        $cache = $this->cache->loadKeyringCache();
        $localKeys = array_values(array_filter((array) ($cache['local_keys'] ?? []), static function ($row) use ($keyId): bool {
            return !is_array($row) || strtolower(trim((string) ($row['key_id'] ?? ''))) !== $keyId;
        }));

        $localKeys[] = [
            'key_id' => $keyId,
            'publisher' => $publisher !== '' ? $publisher : 'local-admin',
            'algorithm' => 'rsa-sha256',
            'scope' => 'local_only',
            'source' => 'local_admin',
            'public_key' => $publicKey,
            'created_at' => gmdate('c'),
            'updated_at' => gmdate('c'),
            'revoked' => false,
        ];

        $cache['local_keys'] = $localKeys;
        $cache['last_sync_status'] = (string) ($cache['last_sync_status'] ?? 'disabled');

        if (!$this->cache->saveKeyringCache($cache)) {
            return ['ok' => false, 'message' => 'Écriture cache trust impossible.'];
        }

        $this->logger->info('Local key added', ['key_id' => $keyId, 'publisher' => $publisher]);

        return ['ok' => true, 'message' => 'Clé locale ajoutée.'];
    }

    public function removeLocalKey(string $keyId): array
    {
        $keyId = strtolower(trim($keyId));
        if ($keyId === '') {
            return ['ok' => false, 'message' => 'key_id requis.'];
        }

        $existing = $this->find($keyId);
        if (!is_array($existing)) {
            return ['ok' => false, 'message' => 'Clé introuvable.'];
        }
        if (!$this->resolver->isEditable((string) ($existing['scope'] ?? ''))) {
            return ['ok' => false, 'message' => 'Suppression refusée: clé non locale.'];
        }

        $cache = $this->cache->loadKeyringCache();
        $before = count((array) ($cache['local_keys'] ?? []));
        $cache['local_keys'] = array_values(array_filter((array) ($cache['local_keys'] ?? []), static function ($row) use ($keyId): bool {
            return !is_array($row) || strtolower(trim((string) ($row['key_id'] ?? ''))) !== $keyId;
        }));
        $after = count((array) ($cache['local_keys'] ?? []));

        if ($after === $before) {
            return ['ok' => false, 'message' => 'Clé locale introuvable.'];
        }

        if (!$this->cache->saveKeyringCache($cache)) {
            return ['ok' => false, 'message' => 'Écriture cache trust impossible.'];
        }

        $this->logger->warning('Local key removed', ['key_id' => $keyId]);

        return ['ok' => true, 'message' => 'Clé locale supprimée.'];
    }

    public function syncRemote(): array
    {
        $remoteConfig = (array) config('keyring.remote', []);
        $enabled = (bool) ($remoteConfig['enabled'] ?? false) && (bool) config('trust-policy.allow_remote_sync', false);
        if (!$enabled) {
            return ['ok' => false, 'message' => 'Sync distante désactivée.', 'status' => 'disabled'];
        }

        $keyringUrl = trim((string) ($remoteConfig['keyring_url'] ?? ''));
        $registryUrl = trim((string) ($remoteConfig['registry_url'] ?? ''));
        if ($keyringUrl === '' || $registryUrl === '') {
            return ['ok' => false, 'message' => 'Endpoints distants non configurés.', 'status' => 'missing_config'];
        }

        $timeout = max(2, (int) ($remoteConfig['timeout'] ?? 5));
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => $timeout,
                'ignore_errors' => true,
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);

        $keyringRaw = @file_get_contents($keyringUrl, false, $context);
        $registryRaw = @file_get_contents($registryUrl, false, $context);

        $keyringDecoded = is_string($keyringRaw) ? json_decode($keyringRaw, true) : null;
        $registryDecoded = is_string($registryRaw) ? json_decode($registryRaw, true) : null;
        if (!is_array($keyringDecoded) || !is_array($registryDecoded)) {
            $this->updateSyncMeta('error', 'Sync distante invalide.');
            return ['ok' => false, 'message' => 'Réponse distante invalide.', 'status' => 'error'];
        }

        $cache = $this->cache->loadKeyringCache();
        $cache['remote_keys'] = array_values((array) ($keyringDecoded['keys'] ?? []));
        $cache['last_sync_at'] = gmdate('c');
        $cache['last_sync_status'] = 'ok';
        $cache['last_sync_message'] = 'Sync distante réussie.';

        $okKeyring = $this->cache->saveKeyringCache($cache);
        $okRegistry = $this->cache->saveRegistryCache([
            'publishers' => array_values((array) ($registryDecoded['publishers'] ?? [])),
            'keys' => array_values((array) ($registryDecoded['keys'] ?? [])),
        ]);

        if (!$okKeyring || !$okRegistry) {
            return ['ok' => false, 'message' => 'Écriture cache distante impossible.', 'status' => 'error'];
        }

        $this->logger->info('Remote trust sync completed', ['keyring_url' => $keyringUrl]);

        return ['ok' => true, 'message' => 'Sync distante terminée.', 'status' => 'ok'];
    }

    private function embeddedEntries(): array
    {
        $entries = [];
        $scopes = ['official', 'trusted', 'community'];
        foreach ($scopes as $scope) {
            foreach ((array) config('keyring.' . $scope, []) as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $entries[] = $this->normalizeEntry($row, $scope, 'embedded', false);
            }
        }

        foreach ((array) config('module-trust.keyring', []) as $legacyKeyId => $legacyPublicKey) {
            if (!is_string($legacyKeyId) || !is_string($legacyPublicKey)) {
                continue;
            }
            $entries[] = $this->normalizeEntry([
                'key_id' => $legacyKeyId,
                'publisher' => 'legacy',
                'public_key' => $legacyPublicKey,
            ], 'official', 'embedded_legacy', false);
        }

        return array_values(array_filter($entries, static fn (array $entry): bool => (string) ($entry['key_id'] ?? '') !== '' && (string) ($entry['public_key'] ?? '') !== ''));
    }

    private function localEntries(): array
    {
        $cache = $this->cache->loadKeyringCache();
        $entries = [];
        foreach ((array) ($cache['local_keys'] ?? []) as $row) {
            if (!is_array($row)) {
                continue;
            }
            $entries[] = $this->normalizeEntry($row, 'local_only', 'local_admin', true);
        }

        return $entries;
    }

    private function remoteEntries(): array
    {
        $cache = $this->cache->loadKeyringCache();
        $entries = [];
        foreach ((array) ($cache['remote_keys'] ?? []) as $row) {
            if (!is_array($row)) {
                continue;
            }
            $scope = $this->resolver->normalize((string) ($row['scope'] ?? 'community'));
            $entries[] = $this->normalizeEntry($row, $scope, 'registry_cache', false);
        }

        return $entries;
    }

    private function revokedKeyIds(): array
    {
        $cache = $this->cache->loadKeyringCache();
        $fromCache = array_map(
            static fn ($v): string => strtolower(trim((string) $v)),
            (array) ($cache['revoked'] ?? [])
        );
        $fromConfig = array_map(
            static fn ($v): string => strtolower(trim((string) $v)),
            (array) config('keyring.revoked', [])
        );

        return array_values(array_unique(array_filter(array_merge($fromCache, $fromConfig), static fn (string $v): bool => $v !== '')));
    }

    private function normalizeEntry(array $row, string $scope, string $source, bool $editable): array
    {
        $keyId = strtolower(trim((string) ($row['key_id'] ?? '')));
        $publicKey = trim((string) ($row['public_key'] ?? ''));
        $publisher = strtolower(trim((string) ($row['publisher'] ?? 'unknown')));

        return [
            'key_id' => $keyId,
            'publisher' => $publisher,
            'algorithm' => strtolower(trim((string) ($row['algorithm'] ?? 'rsa-sha256'))),
            'scope' => $this->resolver->normalize((string) ($row['scope'] ?? $scope)),
            'source' => (string) ($row['source'] ?? $source),
            'public_key' => $publicKey,
            'fingerprint' => hash('sha256', $publicKey),
            'editable' => $editable,
            'revoked' => (bool) ($row['revoked'] ?? false),
            'created_at' => (string) ($row['created_at'] ?? ''),
            'updated_at' => (string) ($row['updated_at'] ?? ''),
        ];
    }

    private function mergeEntry(array $entries, array $entry): array
    {
        $keyId = (string) ($entry['key_id'] ?? '');
        if ($keyId === '') {
            return $entries;
        }

        if (!isset($entries[$keyId])) {
            $entries[$keyId] = $entry;
            return $entries;
        }

        $current = $entries[$keyId];
        $currentRank = $this->resolver->rank((string) ($current['scope'] ?? 'community'));
        $incomingRank = $this->resolver->rank((string) ($entry['scope'] ?? 'community'));

        if ($incomingRank > $currentRank) {
            $entries[$keyId] = $entry;
        }

        return $entries;
    }

    private function updateSyncMeta(string $status, string $message): void
    {
        $cache = $this->cache->loadKeyringCache();
        $cache['last_sync_at'] = gmdate('c');
        $cache['last_sync_status'] = $status;
        $cache['last_sync_message'] = $message;
        $this->cache->saveKeyringCache($cache);
    }
}
