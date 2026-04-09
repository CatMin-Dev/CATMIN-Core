<?php

declare(strict_types=1);

require_once CATMIN_CORE . '/market-github.php';
require_once CATMIN_CORE . '/market-installer.php';
require_once CATMIN_CORE . '/module-loader.php';
require_once CATMIN_CORE . '/module-compatibility-checker.php';
require_once CATMIN_CORE . '/module-capability-policy.php';
require_once CATMIN_CORE . '/module-integrity-scanner.php';
require_once CATMIN_CORE . '/module-repository-registry.php';
require_once CATMIN_CORE . '/module-repository-index-standard.php';
require_once CATMIN_CORE . '/module-trust-score.php';

final class CoreMarketEngine
{
    public function catalog(): array
    {
        $registry = new CoreModuleRepositoryRegistry();
        $repositories = $registry->getEnabledRepositories();
        $policy = $registry->policy();

        if ($repositories === []) {
            return [
                'ok' => false,
                'error' => 'Aucun dépôt module actif.',
                'items' => [],
                'stats' => [
                    'total' => 0,
                    'installed' => 0,
                    'updates' => 0,
                    'incompatible' => 0,
                ],
            ];
        }

        $localBySlug = $this->localModulesBySlug();
        $integrityBySlug = $this->integrityBySlug();
        $trust = $registry->trustEvaluator();

        $indexed = [];
        $errors = [];
        $scorer = new CoreModuleTrustScore();

        foreach ($repositories as $repository) {
            $remote = $this->catalogFromRepository($repository);
            if (!(bool) ($remote['ok'] ?? false)) {
                $errors[] = (string) ($remote['error'] ?? 'Catalogue indisponible.');
                continue;
            }

            $repoItems = is_array($remote['items'] ?? null) ? $remote['items'] : [];
            foreach ($repoItems as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $slug = strtolower(trim((string) ($item['slug'] ?? '')));
                $scope = strtolower(trim((string) ($item['scope'] ?? 'unknown')));
                if ($slug === '') {
                    continue;
                }

                $manifest = is_array($item['manifest'] ?? null) ? $item['manifest'] : [];
                if (!isset($manifest['release_channel']) && isset($item['release_channel'])) {
                    $manifest['release_channel'] = (string) $item['release_channel'];
                }
                if (!isset($manifest['lifecycle_status']) && isset($item['lifecycle_status'])) {
                    $manifest['lifecycle_status'] = (string) $item['lifecycle_status'];
                }
                $local = $localBySlug[$slug] ?? null;
                $integrity = $integrityBySlug[$slug] ?? null;
                $compat = (new CoreModuleCompatibilityChecker())->check($manifest);
                $capabilityState = (new CoreModuleCapabilityPolicy())->evaluate($manifest, (string) ($repository['trust_level'] ?? 'community'));
                $trustState = $trust->evaluate($repository, $manifest, $policy);
                $repoAllowedChannels = array_values(array_filter(array_map(
                    static fn ($v): string => strtolower(trim((string) $v)),
                    preg_split('/[,\s]+/', (string) ($repository['allowed_release_channels'] ?? 'stable,beta,dev,alpha,experimental')) ?: []
                ), static fn (string $v): bool => $v !== ''));
                $channel = strtolower((string) ($manifest['release_channel'] ?? 'stable'));
                if ($repoAllowedChannels !== [] && !in_array($channel, $repoAllowedChannels, true)) {
                    $trustState['install_allowed'] = false;
                    $trustState['warnings'][] = 'Canal non autorisé par dépôt: ' . $channel;
                }
                if (!(bool) ($capabilityState['ok'] ?? false)) {
                    $trustState['install_allowed'] = false;
                }

                if (!(bool) ($trustState['visible'] ?? true)) {
                    continue;
                }

                $row = [
                    'repo_id' => (int) ($repository['id'] ?? 0),
                    'repo_slug' => (string) ($repository['slug'] ?? ''),
                    'repo_name' => (string) ($repository['name'] ?? ''),
                    'repo_provider' => (string) ($repository['provider'] ?? ''),
                    'repo_trust_level' => (string) ($repository['trust_level'] ?? 'community'),
                    'scope' => $scope,
                    'slug' => $slug,
                    'name' => (string) ($item['name'] ?? strtoupper($slug)),
                    'description' => (string) ($item['description'] ?? ''),
                    'version' => (string) ($item['version'] ?? '0.0.0'),
                    'catmin_min' => (string) ($item['catmin_min'] ?? ''),
                    'catmin_max' => (string) ($item['catmin_max'] ?? ''),
                    'zip_url' => (string) ($item['zip_url'] ?? ''),
                    'path_in_zip' => (string) ($item['path_in_zip'] ?? ''),
                    'release_channel' => strtolower((string) ($manifest['release_channel'] ?? ($item['release_channel'] ?? 'stable'))),
                    'lifecycle_status' => strtolower((string) ($manifest['lifecycle_status'] ?? ($item['lifecycle_status'] ?? 'active'))),
                    'replacement_slug' => strtolower((string) ($manifest['replacement_slug'] ?? ($item['replacement_slug'] ?? ''))),
                    'deprecation_message' => (string) ($manifest['deprecation_message'] ?? ($item['deprecation_message'] ?? '')),
                    'readme_url' => (string) ($item['readme_url'] ?? ($manifest['readme_url'] ?? '')),
                    'changelog_url' => (string) ($item['changelog_url'] ?? ($manifest['changelog_url'] ?? '')),
                    'checksums_url' => (string) ($item['checksums_url'] ?? ''),
                    'signature_url' => (string) ($item['signature_url'] ?? ''),
                    'manifest' => $manifest,
                    'installed' => is_array($local),
                    'enabled' => (bool) ($local['enabled'] ?? false),
                    'installed_version' => is_array($local) ? (string) ($local['version'] ?? '0.0.0') : '',
                    'has_update' => is_array($local) ? version_compare((string) ($item['version'] ?? '0.0.0'), (string) ($local['version'] ?? '0.0.0'), '>') : false,
                    'compatible' => (bool) ($compat['compatible'] ?? false),
                    'compatibility_state' => (string) ($compat['state'] ?? 'unknown'),
                    'compat_errors' => (array) ($compat['errors'] ?? []),
                    'compat_warnings' => (array) ($compat['warnings'] ?? []),
                    'integrity_status' => is_array($integrity) ? (string) ($integrity['integrity_status'] ?? 'unknown') : 'n/a',
                    'signature_status' => is_array($integrity) ? (string) ($integrity['signature_status'] ?? 'unknown') : 'n/a',
                    'trusted' => is_array($integrity) ? (bool) ($integrity['trusted'] ?? false) : false,
                    'install_allowed' => (bool) ($trustState['install_allowed'] ?? false),
                    'trust_warnings' => (array) ($trustState['warnings'] ?? []),
                    'capabilities' => (array) ($capabilityState['capabilities'] ?? []),
                    'capabilities_warnings' => (array) ($capabilityState['warnings'] ?? []),
                    'capabilities_errors' => (array) ($capabilityState['errors'] ?? []),
                    'capabilities_risk' => (string) ($capabilityState['risk_level'] ?? 'low'),
                ];
                $row['trust_score'] = $scorer->evaluate($row);

                $key = $scope . '/' . $slug;
                if (!isset($indexed[$key])) {
                    $indexed[$key] = $row;
                    continue;
                }

                $current = $indexed[$key];
                $currentScore = $trust->scoreLevel((string) ($current['repo_trust_level'] ?? 'community'));
                $incomingScore = $trust->scoreLevel((string) ($row['repo_trust_level'] ?? 'community'));

                if ($incomingScore > $currentScore) {
                    $indexed[$key] = $row;
                    continue;
                }

                if ($incomingScore === $currentScore && version_compare((string) $row['version'], (string) $current['version'], '>')) {
                    $indexed[$key] = $row;
                }
            }
        }

        $items = array_values($indexed);
        usort($items, static function (array $a, array $b) use ($trust): int {
            $ta = $trust->scoreLevel((string) ($a['repo_trust_level'] ?? 'community'));
            $tb = $trust->scoreLevel((string) ($b['repo_trust_level'] ?? 'community'));
            if ($ta !== $tb) {
                return $tb <=> $ta;
            }
            return strcmp((string) (($a['scope'] ?? '') . '/' . ($a['slug'] ?? '')), (string) (($b['scope'] ?? '') . '/' . ($b['slug'] ?? '')));
        });

        return [
            'ok' => $items !== [] || $errors === [],
            'error' => $errors !== [] && $items === [] ? implode(' | ', array_unique($errors)) : '',
            'items' => $items,
            'policy' => $policy,
            'stats' => [
                'total' => count($items),
                'installed' => count(array_filter($items, static fn (array $row): bool => (bool) ($row['installed'] ?? false))),
                'updates' => count(array_filter($items, static fn (array $row): bool => (bool) ($row['has_update'] ?? false))),
                'incompatible' => count(array_filter($items, static fn (array $row): bool => !((bool) ($row['compatible'] ?? true)))),
                'capability_risk' => count(array_filter($items, static fn (array $row): bool => in_array((string) ($row['capabilities_risk'] ?? 'low'), ['high', 'critical'], true))),
            ],
        ];
    }

    public function install(array $catalogItem): array
    {
        if (!((bool) ($catalogItem['install_allowed'] ?? true))) {
            return [
                'ok' => false,
                'message' => 'Installation refusée par policy trust du dépôt.',
                'errors' => ['repository_policy_denied'],
            ];
        }

        return (new CoreMarketInstaller())->installFromCatalogItem($catalogItem);
    }

    private function catalogFromRepository(array $repository): array
    {
        $provider = strtolower(trim((string) ($repository['provider'] ?? 'github')));
        $branch = trim((string) ($repository['branch_or_channel'] ?? 'main'));
        $requiresStandard = (bool) ($repository['requires_manifest_standard'] ?? true);

        if ($provider === 'github') {
            $repo = $this->extractGithubRepo($repository);
            if ($repo === null) {
                return ['ok' => false, 'error' => 'Repo GitHub invalide', 'items' => []];
            }
            $catalog = (new CoreMarketGithub($repo, $branch))->catalog();
            if (!(bool) ($catalog['ok'] ?? false) && $requiresStandard) {
                return ['ok' => false, 'error' => 'Dépôt non conforme standard CATMIN (catmin-repository.json requis).', 'items' => []];
            }
            if ((bool) ($catalog['ok'] ?? false) && $requiresStandard && !((bool) ($catalog['standard_index'] ?? false))) {
                return ['ok' => false, 'error' => 'Dépôt non standard: catmin-repository.json requis.', 'items' => []];
            }
            return $catalog;
        }

        if ($provider === 'custom_http_index') {
            $indexUrl = trim((string) ($repository['index_url'] ?? ''));
            if ($indexUrl === '' || filter_var($indexUrl, FILTER_VALIDATE_URL) === false) {
                return ['ok' => false, 'error' => 'Index URL invalide', 'items' => []];
            }

            $raw = $this->requestRaw($indexUrl);
            if (!is_string($raw) || $raw === '') {
                return ['ok' => false, 'error' => 'Index inaccessible', 'items' => []];
            }

            $decoded = json_decode($raw, true);
            if (!is_array($decoded)) {
                return ['ok' => false, 'error' => 'Index JSON invalide', 'items' => []];
            }

            $standard = (new CoreModuleRepositoryIndexStandard())->parse($decoded);
            if ((bool) ($standard['ok'] ?? false)) {
                return ['ok' => true, 'error' => '', 'items' => (array) ($standard['items'] ?? [])];
            }
            if ($requiresStandard) {
                return ['ok' => false, 'error' => 'Index non conforme standard CATMIN.', 'items' => []];
            }

            $items = is_array($decoded['items'] ?? null) ? $decoded['items'] : [];
            return ['ok' => true, 'error' => '', 'items' => $items];
        }

        if ($provider === 'local_mirror') {
            $indexPath = trim((string) ($repository['index_url'] ?? ''));
            $repoPath = trim((string) ($repository['repo_url'] ?? ''));
            $resolvedPath = $this->resolveLocalMirrorIndexPath($indexPath, $repoPath);
            if ($resolvedPath === null || !is_file($resolvedPath)) {
                return ['ok' => false, 'error' => 'Index local miroir introuvable.', 'items' => []];
            }

            $raw = @file_get_contents($resolvedPath);
            $decoded = is_string($raw) ? json_decode($raw, true) : null;
            if (!is_array($decoded)) {
                return ['ok' => false, 'error' => 'Index local miroir invalide.', 'items' => []];
            }

            $standard = (new CoreModuleRepositoryIndexStandard())->parse($decoded);
            if (!(bool) ($standard['ok'] ?? false)) {
                return ['ok' => false, 'error' => 'Index local non conforme standard CATMIN.', 'items' => []];
            }

            $items = is_array($standard['items'] ?? null) ? $standard['items'] : [];
            foreach ($items as &$item) {
                if (!is_array($item)) {
                    continue;
                }
                $zip = trim((string) ($item['zip_url'] ?? ''));
                if ($zip !== '' && filter_var($zip, FILTER_VALIDATE_URL) === false) {
                    $item['zip_url'] = $this->resolveLocalMirrorAsset($zip, dirname($resolvedPath));
                }
                $checksums = trim((string) ($item['checksums_url'] ?? ''));
                if ($checksums !== '' && filter_var($checksums, FILTER_VALIDATE_URL) === false) {
                    $item['checksums_url'] = $this->resolveLocalMirrorAsset($checksums, dirname($resolvedPath));
                }
                $signature = trim((string) ($item['signature_url'] ?? ''));
                if ($signature !== '' && filter_var($signature, FILTER_VALIDATE_URL) === false) {
                    $item['signature_url'] = $this->resolveLocalMirrorAsset($signature, dirname($resolvedPath));
                }
            }
            unset($item);

            return ['ok' => true, 'error' => '', 'items' => $items];
        }

        return ['ok' => false, 'error' => 'Provider non supporté', 'items' => []];
    }

    private function localModulesBySlug(): array
    {
        $scan = (new CoreModuleLoader())->scan();
        $rows = [];
        foreach ((array) ($scan['modules'] ?? []) as $module) {
            if (!is_array($module)) {
                continue;
            }
            $manifest = is_array($module['manifest'] ?? null) ? $module['manifest'] : [];
            $slug = strtolower(trim((string) ($manifest['slug'] ?? '')));
            if ($slug === '') {
                continue;
            }
            $rows[$slug] = [
                'slug' => $slug,
                'scope' => strtolower(trim((string) ($manifest['type'] ?? ''))),
                'version' => (string) ($manifest['version'] ?? '0.0.0'),
                'enabled' => (bool) ($module['enabled'] ?? false),
            ];
        }
        return $rows;
    }

    private function integrityBySlug(): array
    {
        $report = (new CoreModuleIntegrityScanner())->scanAll(false);
        $rows = [];
        foreach ((array) ($report['modules'] ?? []) as $row) {
            $slug = strtolower(trim((string) ($row['slug'] ?? '')));
            if ($slug !== '') {
                $rows[$slug] = $row;
            }
        }
        return $rows;
    }

    private function extractGithubRepo(array $repository): ?string
    {
        $repoUrl = trim((string) ($repository['repo_url'] ?? ''));
        $apiUrl = trim((string) ($repository['api_url'] ?? ''));

        if (preg_match('#^([A-Za-z0-9_.-]+)/([A-Za-z0-9_.-]+)$#', $repoUrl, $m) === 1) {
            return $m[1] . '/' . $m[2];
        }

        if (filter_var($repoUrl, FILTER_VALIDATE_URL) !== false) {
            $host = strtolower((string) parse_url($repoUrl, PHP_URL_HOST));
            $path = trim((string) parse_url($repoUrl, PHP_URL_PATH), '/');
            if (in_array($host, ['github.com', 'www.github.com'], true)) {
                $parts = explode('/', $path);
                if (count($parts) >= 2) {
                    return $parts[0] . '/' . preg_replace('/\.git$/i', '', $parts[1]);
                }
            }
        }

        if (filter_var($apiUrl, FILTER_VALIDATE_URL) !== false) {
            $path = trim((string) parse_url($apiUrl, PHP_URL_PATH), '/');
            if (preg_match('#^repos/([^/]+)/([^/]+)$#', $path, $m) === 1) {
                return $m[1] . '/' . $m[2];
            }
        }

        return null;
    }

    private function requestRaw(string $url): ?string
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            if ($ch === false) {
                return null;
            }
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_TIMEOUT => 20,
                CURLOPT_HTTPHEADER => ['User-Agent: CATMIN-Market'],
            ]);
            $body = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            curl_close($ch);
            if (!is_string($body) || $code < 200 || $code >= 300) {
                return null;
            }
            return $body;
        }

        $body = @file_get_contents($url);
        return is_string($body) ? $body : null;
    }

    private function resolveLocalMirrorIndexPath(string $indexPath, string $repoPath): ?string
    {
        $candidates = [];
        if ($indexPath !== '') {
            $candidates[] = $indexPath;
        }
        if ($repoPath !== '') {
            $candidates[] = rtrim($repoPath, '/') . '/catmin-repository.json';
            $candidates[] = $repoPath;
        }

        foreach ($candidates as $candidate) {
            $candidate = trim($candidate);
            if ($candidate === '') {
                continue;
            }
            if (filter_var($candidate, FILTER_VALIDATE_URL) !== false) {
                continue;
            }
            $path = $candidate;
            if (!str_starts_with($path, '/')) {
                $path = CATMIN_ROOT . '/' . ltrim($path, '/');
            }
            $real = realpath($path);
            if (is_string($real) && is_file($real)) {
                return $real;
            }
        }

        return null;
    }

    private function resolveLocalMirrorAsset(string $relativePath, string $indexDir): string
    {
        $relativePath = ltrim(str_replace('\\', '/', trim($relativePath)), '/');
        if ($relativePath === '') {
            return '';
        }
        $full = realpath($indexDir . '/' . $relativePath);
        if (!is_string($full) || !is_file($full)) {
            return '';
        }
        return $full;
    }
}
