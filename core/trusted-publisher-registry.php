<?php

declare(strict_types=1);

require_once CATMIN_CORE . '/keyring-cache.php';
require_once CATMIN_CORE . '/trust-scope-resolver.php';

final class CoreTrustedPublisherRegistry
{
    public function __construct(
        private readonly CoreKeyringCache $cache = new CoreKeyringCache(),
        private readonly CoreTrustScopeResolver $resolver = new CoreTrustScopeResolver(),
    ) {}

    public function all(): array
    {
        $items = [];

        foreach ((array) config('keyring.trusted_publishers', []) as $row) {
            if (!is_array($row)) {
                continue;
            }
            $publisher = strtolower(trim((string) ($row['publisher'] ?? '')));
            if ($publisher === '') {
                continue;
            }
            $items[$publisher] = [
                'publisher' => $publisher,
                'trust_scope' => $this->resolver->normalize((string) ($row['trust_scope'] ?? 'trusted')),
                'source' => (string) ($row['source'] ?? 'embedded'),
            ];
        }

        $cached = $this->cache->loadRegistryCache();
        foreach ((array) ($cached['publishers'] ?? []) as $row) {
            if (!is_array($row)) {
                continue;
            }
            $publisher = strtolower(trim((string) ($row['publisher'] ?? '')));
            if ($publisher === '') {
                continue;
            }
            $items[$publisher] = [
                'publisher' => $publisher,
                'trust_scope' => $this->resolver->normalize((string) ($row['trust_scope'] ?? 'trusted')),
                'source' => (string) ($row['source'] ?? 'registry_cache'),
            ];
        }

        ksort($items);

        return array_values($items);
    }
}
