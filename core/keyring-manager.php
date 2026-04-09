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

        foreach ($this->importedOfficialEntries() as $entry) {
            $merged = $this->mergeEntry($merged, $entry);
        }

        foreach ($this->remoteEntries() as $entry) {
            $merged = $this->mergeEntry($merged, $entry);
        }

        foreach ($this->localEntries() as $entry) {
            $merged = $this->mergeEntry($merged, $entry);
        }

        $revocations = $this->revocationMap();
        foreach ($merged as $keyId => $entry) {
            if (isset($revocations[$keyId])) {
                $entry['status'] = 'revoked';
                $entry['scope'] = 'revoked';
                $entry['revoked'] = true;
                $entry['revoked_at'] = (string) ($revocations[$keyId]['revoked_at'] ?? $entry['revoked_at'] ?? gmdate('c'));
                $entry['revocation_reason'] = (string) ($revocations[$keyId]['reason'] ?? '');
                $entry['editable'] = false;
            }
            $merged[$keyId] = $entry;
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
            $status = strtolower((string) ($entry['status'] ?? 'active'));
            if (!$this->resolver->isUsable($scope) || $status === 'revoked') {
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
        $keyId = strtolower(trim($keyId));
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
        $status = 'local_only';

        if ($keyId === '' || preg_match('/^[a-z0-9._-]{3,120}$/', $keyId) !== 1) {
            return ['ok' => false, 'message' => 'key_id invalide.'];
        }
        if (!$this->isValidPublicKey($publicKey)) {
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
            'owner' => $publisher !== '' ? $publisher : 'local-admin',
            'algorithm' => 'rsa-sha256',
            'scope' => 'local_only',
            'status' => $status,
            'source' => 'local_admin',
            'public_key' => $publicKey,
            'created_at' => gmdate('c'),
            'updated_at' => gmdate('c'),
            'deprecated_at' => null,
            'revoked_at' => null,
            'revoked' => false,
        ];

        $cache['local_keys'] = $localKeys;
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

        if (count((array) ($cache['local_keys'] ?? [])) === $before) {
            return ['ok' => false, 'message' => 'Clé locale introuvable.'];
        }

        if (!$this->cache->saveKeyringCache($cache)) {
            return ['ok' => false, 'message' => 'Écriture cache trust impossible.'];
        }

        $this->logger->warning('Local key removed', ['key_id' => $keyId]);

        return ['ok' => true, 'message' => 'Clé locale supprimée.'];
    }

    public function revokeKey(string $keyId, string $reason = ''): array
    {
        $keyId = strtolower(trim($keyId));
        if ($keyId === '') {
            return ['ok' => false, 'message' => 'key_id requis.'];
        }

        $entry = $this->find($keyId);
        if (!is_array($entry)) {
            return ['ok' => false, 'message' => 'Clé introuvable.'];
        }

        $protectAnchors = (bool) config('trust-policy.protect_official_anchors', true);
        $scope = (string) ($entry['scope'] ?? 'community');
        if ($protectAnchors && $scope === 'official') {
            return ['ok' => false, 'message' => 'Révocation locale refusée pour une trust anchor officielle.'];
        }

        $cache = $this->cache->loadKeyringCache();
        $revocations = array_values((array) ($cache['revocations'] ?? []));
        $revocations = array_values(array_filter($revocations, static function ($row) use ($keyId): bool {
            return !is_array($row) || strtolower(trim((string) ($row['key_id'] ?? ''))) !== $keyId;
        }));

        $revocations[] = [
            'key_id' => $keyId,
            'reason' => trim($reason) !== '' ? trim($reason) : 'manual_revocation',
            'revoked_at' => gmdate('c'),
            'source' => 'local_admin',
        ];
        $cache['revocations'] = $revocations;
        $cache['revoked'] = array_values(array_unique(array_merge(
            array_map(static fn ($row): string => strtolower(trim((string) ($row['key_id'] ?? ''))), $revocations),
            (array) ($cache['revoked'] ?? [])
        )));

        if (!$this->cache->saveKeyringCache($cache)) {
            return ['ok' => false, 'message' => 'Écriture cache trust impossible.'];
        }

        $this->logger->warning('Key revoked', ['key_id' => $keyId, 'reason' => $reason]);

        return ['ok' => true, 'message' => 'Clé révoquée localement.'];
    }

    public function importOfficialKeyringFromJson(string $rawJson): array
    {
        if (!(bool) config('trust-policy.allow_manual_import', true)) {
            return ['ok' => false, 'message' => 'Import manuel désactivé par policy.'];
        }

        $decoded = json_decode($rawJson, true);
        if (!is_array($decoded)) {
            return ['ok' => false, 'message' => 'JSON import invalide.'];
        }

        $rows = [];
        if (array_is_list($decoded)) {
            $rows = $decoded;
        } elseif (is_array($decoded['keys'] ?? null)) {
            $rows = (array) $decoded['keys'];
        }
        if ($rows === []) {
            return ['ok' => false, 'message' => 'Aucune clé importable trouvée.'];
        }

        $importable = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $scope = $this->resolver->normalize((string) ($row['scope'] ?? 'official'));
            if (!in_array($scope, ['official', 'trusted', 'community'], true)) {
                continue;
            }

            $normalized = $this->normalizeEntry($row, $scope, 'imported_manual', false);
            if ($normalized['key_id'] === '' || !$this->isValidPublicKey((string) $normalized['public_key'])) {
                continue;
            }
            $importable[] = $normalized;
        }

        if ($importable === []) {
            return ['ok' => false, 'message' => 'Aucune clé valide dans le fichier d’import.'];
        }

        $before = $this->countBySourceScope('official', ['embedded', 'embedded_legacy', 'imported_manual']);

        $cache = $this->cache->loadKeyringCache();
        $cache['imported_official_keys'] = $importable;
        $cache['last_import_at'] = gmdate('c');
        $cache['last_import_status'] = 'ok';
        $cache['last_import_message'] = 'Import manuel keyring officiel terminé.';

        if (!$this->cache->saveKeyringCache($cache)) {
            return ['ok' => false, 'message' => 'Écriture cache import impossible.'];
        }

        $after = $this->countBySourceScope('official', ['embedded', 'embedded_legacy', 'imported_manual']);
        $summary = sprintf('Import keyring: official %d -> %d', $before, $after);
        $this->logger->info('Manual keyring import completed', [
            'before_official' => $before,
            'after_official' => $after,
            'imported_count' => count($importable),
        ]);

        return ['ok' => true, 'message' => $summary];
    }

    public function syncRemote(): array
    {
        $remoteConfig = (array) config('keyring.remote', []);
        $enabled = (bool) ($remoteConfig['enabled'] ?? false) && (bool) config('trust-policy.allow_remote_sync', false);
        if (!$enabled) {
            return ['ok' => false, 'message' => 'Sync distante désactivée.', 'status' => 'disabled'];
        }

        $timeout = max(2, (int) ($remoteConfig['timeout'] ?? 5));
        $keyringDecoded = $this->fetchJson((string) ($remoteConfig['keyring_url'] ?? ''), $timeout);
        $registryDecoded = $this->fetchJson((string) ($remoteConfig['registry_url'] ?? ''), $timeout);
        $revocationsDecoded = $this->fetchJson((string) ($remoteConfig['revocations_url'] ?? ''), $timeout, true);
        $publishersDecoded = $this->fetchJson((string) ($remoteConfig['publishers_url'] ?? ''), $timeout, true);
        $metadataDecoded = $this->fetchJson((string) ($remoteConfig['metadata_url'] ?? ''), $timeout, true);

        if (!is_array($keyringDecoded) || !is_array($registryDecoded)) {
            $this->updateSyncMeta('error', 'Sync distante invalide.');
            return ['ok' => false, 'message' => 'Réponse distante invalide.', 'status' => 'error'];
        }

        $remoteKeys = [];
        foreach ((array) ($keyringDecoded['keys'] ?? []) as $row) {
            if (!is_array($row)) {
                continue;
            }
            $scope = $this->resolver->normalize((string) ($row['scope'] ?? 'community'));
            $normalized = $this->normalizeEntry($row, $scope, 'synced', false);
            if ($normalized['key_id'] === '' || !$this->isValidPublicKey((string) $normalized['public_key'])) {
                continue;
            }
            $remoteKeys[] = $normalized;
        }

        $revocations = [];
        foreach ((array) (($revocationsDecoded['revocations'] ?? $registryDecoded['revocations'] ?? []) ?: []) as $row) {
            if (is_string($row)) {
                $row = ['key_id' => $row];
            }
            if (!is_array($row)) {
                continue;
            }
            $keyId = strtolower(trim((string) ($row['key_id'] ?? '')));
            if ($keyId === '') {
                continue;
            }
            $revocations[] = [
                'key_id' => $keyId,
                'reason' => (string) ($row['reason'] ?? 'remote_revocation'),
                'revoked_at' => (string) ($row['revoked_at'] ?? gmdate('c')),
                'source' => 'remote_registry',
            ];
        }

        $publishers = [];
        foreach ((array) (($publishersDecoded['publishers'] ?? $registryDecoded['publishers'] ?? []) ?: []) as $row) {
            if (!is_array($row)) {
                continue;
            }
            $publisher = strtolower(trim((string) ($row['publisher'] ?? '')));
            if ($publisher === '') {
                continue;
            }
            $publishers[] = [
                'publisher' => $publisher,
                'trust_scope' => $this->resolver->normalize((string) ($row['trust_scope'] ?? 'trusted')),
                'source' => 'registry_sync',
            ];
        }

        $cache = $this->cache->loadKeyringCache();
        $cache['remote_keys'] = $remoteKeys;
        $cache['revocations'] = $revocations;
        $cache['revoked'] = array_values(array_unique(array_map(
            static fn ($row): string => strtolower(trim((string) ($row['key_id'] ?? ''))),
            $revocations
        )));
        $cache['last_sync_at'] = gmdate('c');
        $cache['last_sync_status'] = 'ok';
        $cache['last_sync_message'] = 'Sync distante réussie.';

        $okKeyring = $this->cache->saveKeyringCache($cache);
        $okRegistry = $this->cache->saveRegistryCache([
            'publishers' => $publishers,
            'keys' => array_values((array) ($registryDecoded['keys'] ?? [])),
            'metadata' => is_array($metadataDecoded) ? $metadataDecoded : [],
        ]);

        if (!$okKeyring || !$okRegistry) {
            return ['ok' => false, 'message' => 'Écriture cache distante impossible.', 'status' => 'error'];
        }

        $this->logger->info('Remote trust sync completed', [
            'keyring_url' => (string) ($remoteConfig['keyring_url'] ?? ''),
            'keys' => count($remoteKeys),
            'revocations' => count($revocations),
        ]);

        return ['ok' => true, 'message' => 'Sync distante terminée.', 'status' => 'ok'];
    }

    private function embeddedEntries(): array
    {
        $entries = [];
        foreach (['official', 'trusted', 'community'] as $scope) {
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
                'owner' => 'legacy',
                'status' => 'active',
                'public_key' => $legacyPublicKey,
            ], 'official', 'embedded_legacy', false);
        }

        return array_values(array_filter($entries, static fn (array $entry): bool => $entry['key_id'] !== '' && $entry['public_key'] !== ''));
    }

    private function importedOfficialEntries(): array
    {
        $cache = $this->cache->loadKeyringCache();
        $entries = [];
        foreach ((array) ($cache['imported_official_keys'] ?? []) as $row) {
            if (!is_array($row)) {
                continue;
            }
            $scope = $this->resolver->normalize((string) ($row['scope'] ?? 'official'));
            if (!in_array($scope, ['official', 'trusted', 'community'], true)) {
                continue;
            }
            $entries[] = $this->normalizeEntry($row, $scope, 'imported_manual', false);
        }

        return $entries;
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
            $entries[] = $this->normalizeEntry($row, $scope, 'synced', false);
        }

        return $entries;
    }

    private function revocationMap(): array
    {
        $cache = $this->cache->loadKeyringCache();
        $map = [];

        foreach ((array) config('keyring.revoked', []) as $row) {
            if (is_string($row)) {
                $row = ['key_id' => $row];
            }
            if (!is_array($row)) {
                continue;
            }
            $keyId = strtolower(trim((string) ($row['key_id'] ?? '')));
            if ($keyId === '') {
                continue;
            }
            $map[$keyId] = [
                'key_id' => $keyId,
                'reason' => (string) ($row['reason'] ?? 'config_revocation'),
                'revoked_at' => (string) ($row['revoked_at'] ?? gmdate('c')),
            ];
        }

        foreach ((array) ($cache['revocations'] ?? []) as $row) {
            if (!is_array($row)) {
                continue;
            }
            $keyId = strtolower(trim((string) ($row['key_id'] ?? '')));
            if ($keyId === '') {
                continue;
            }
            $map[$keyId] = [
                'key_id' => $keyId,
                'reason' => (string) ($row['reason'] ?? 'cache_revocation'),
                'revoked_at' => (string) ($row['revoked_at'] ?? gmdate('c')),
            ];
        }

        foreach ((array) ($cache['revoked'] ?? []) as $keyId) {
            $keyId = strtolower(trim((string) $keyId));
            if ($keyId === '') {
                continue;
            }
            if (!isset($map[$keyId])) {
                $map[$keyId] = [
                    'key_id' => $keyId,
                    'reason' => 'legacy_revoked_list',
                    'revoked_at' => gmdate('c'),
                ];
            }
        }

        return $map;
    }

    private function normalizeEntry(array $row, string $scope, string $source, bool $editable): array
    {
        $keyId = strtolower(trim((string) ($row['key_id'] ?? '')));
        $publicKey = trim((string) ($row['public_key'] ?? ''));
        $publisher = strtolower(trim((string) ($row['publisher'] ?? 'unknown')));
        $owner = strtolower(trim((string) ($row['owner'] ?? $publisher)));
        $status = strtolower(trim((string) ($row['status'] ?? 'active')));
        $deprecatedAt = (string) ($row['deprecated_at'] ?? '');
        $revokedAt = (string) ($row['revoked_at'] ?? '');
        $normalizedScope = $this->resolver->normalize((string) ($row['scope'] ?? $scope));

        if ($normalizedScope === 'local_only') {
            $status = 'local_only';
        }
        if ($revokedAt !== '' || (bool) ($row['revoked'] ?? false) || $normalizedScope === 'revoked') {
            $status = 'revoked';
        } elseif ($status === '' || !in_array($status, ['active', 'deprecated', 'revoked', 'local_only', 'pending_trust'], true)) {
            $status = $deprecatedAt !== '' ? 'deprecated' : 'active';
        }

        return [
            'key_id' => $keyId,
            'publisher' => $publisher,
            'owner' => $owner !== '' ? $owner : $publisher,
            'algorithm' => strtolower(trim((string) ($row['algorithm'] ?? 'rsa-sha256'))),
            'scope' => $status === 'revoked' ? 'revoked' : $normalizedScope,
            'status' => $status,
            'source' => (string) ($row['source'] ?? $source),
            'public_key' => $publicKey,
            'fingerprint' => $publicKey !== '' ? hash('sha256', $publicKey) : '',
            'editable' => $editable && $status !== 'revoked',
            'revoked' => $status === 'revoked',
            'created_at' => (string) ($row['created_at'] ?? ''),
            'updated_at' => (string) ($row['updated_at'] ?? ''),
            'deprecated_at' => $deprecatedAt !== '' ? $deprecatedAt : null,
            'revoked_at' => $revokedAt !== '' ? $revokedAt : null,
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
            return $entries;
        }

        if ($incomingRank === $currentRank) {
            $sourceWeight = static fn (string $source): int => match ($source) {
                'embedded' => 500,
                'embedded_legacy' => 450,
                'imported_manual' => 400,
                'synced' => 350,
                'local_admin' => 200,
                default => 100,
            };
            if ($sourceWeight((string) ($entry['source'] ?? '')) > $sourceWeight((string) ($current['source'] ?? ''))) {
                $entries[$keyId] = $entry;
            }
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

    private function fetchJson(string $url, int $timeout, bool $optional = false): ?array
    {
        $url = trim($url);
        if ($url === '') {
            return $optional ? [] : null;
        }

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

        $raw = @file_get_contents($url, false, $context);
        if (!is_string($raw) || trim($raw) === '') {
            return $optional ? [] : null;
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : ($optional ? [] : null);
    }

    private function countBySourceScope(string $scope, array $sources): int
    {
        $scope = $this->resolver->normalize($scope);
        $count = 0;
        foreach ($this->all() as $entry) {
            if ((string) ($entry['scope'] ?? '') !== $scope) {
                continue;
            }
            if (in_array((string) ($entry['source'] ?? ''), $sources, true)) {
                $count++;
            }
        }

        return $count;
    }

    private function isValidPublicKey(string $publicKey): bool
    {
        return $publicKey !== ''
            && str_contains($publicKey, 'BEGIN PUBLIC KEY')
            && str_contains($publicKey, 'END PUBLIC KEY');
    }
}

