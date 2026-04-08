<?php

declare(strict_types=1);

require_once CATMIN_CORE . '/market-github.php';
require_once CATMIN_CORE . '/market-installer.php';
require_once CATMIN_CORE . '/module-loader.php';
require_once CATMIN_CORE . '/module-compatibility-checker.php';
require_once CATMIN_CORE . '/module-integrity-scanner.php';
require_once CATMIN_CORE . '/module-repository-registry.php';

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
                $local = $localBySlug[$slug] ?? null;
                $integrity = $integrityBySlug[$slug] ?? null;
                $compat = (new CoreModuleCompatibilityChecker())->check($manifest);
                $trustState = $trust->evaluate($repository, $manifest, $policy);

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
                    'manifest' => $manifest,
                    'installed' => is_array($local),
                    'enabled' => (bool) ($local['enabled'] ?? false),
                    'installed_version' => is_array($local) ? (string) ($local['version'] ?? '0.0.0') : '',
                    'has_update' => is_array($local) ? version_compare((string) ($item['version'] ?? '0.0.0'), (string) ($local['version'] ?? '0.0.0'), '>') : false,
                    'compatible' => (bool) ($compat['compatible'] ?? false),
                    'compat_errors' => (array) ($compat['errors'] ?? []),
                    'integrity_status' => is_array($integrity) ? (string) ($integrity['integrity_status'] ?? 'unknown') : 'n/a',
                    'signature_status' => is_array($integrity) ? (string) ($integrity['signature_status'] ?? 'unknown') : 'n/a',
                    'trusted' => is_array($integrity) ? (bool) ($integrity['trusted'] ?? false) : false,
                    'install_allowed' => (bool) ($trustState['install_allowed'] ?? false),
                    'trust_warnings' => (array) ($trustState['warnings'] ?? []),
                ];

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
        usort($items, static fn (array $a, array $b): int => strcmp((string) ($a['scope'] . '/' . $a['slug']), (string) ($b['scope'] . '/' . $b['slug'])));

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

        if ($provider === 'github') {
            $repo = $this->extractGithubRepo($repository);
            if ($repo === null) {
                return ['ok' => false, 'error' => 'Repo GitHub invalide', 'items' => []];
            }
            return (new CoreMarketGithub($repo, $branch))->catalog();
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

            $items = is_array($decoded['items'] ?? null) ? $decoded['items'] : [];
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
}
