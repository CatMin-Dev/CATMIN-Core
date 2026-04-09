<?php

declare(strict_types=1);

require_once CATMIN_CORE . '/market-github.php';
require_once CATMIN_CORE . '/module-repository-index-standard.php';

final class CoreModuleRepositoryChecker
{
    /** @return array{ok:bool,status:string,message:string,module_count:int,errors:array<int,string>} */
    public function check(array $repository): array
    {
        $provider = strtolower(trim((string) ($repository['provider'] ?? 'github')));

        return match ($provider) {
            'github' => $this->checkGithub($repository),
            'custom_http_index' => $this->checkCustomIndex($repository),
            'local_mirror' => $this->checkLocalMirror($repository),
            default => [
                'ok' => false,
                'status' => 'invalid',
                'message' => 'Provider non supporté actuellement.',
                'module_count' => 0,
                'errors' => ['provider_not_supported'],
            ],
        };
    }

    private function checkLocalMirror(array $repository): array
    {
        $indexPath = trim((string) ($repository['index_url'] ?? ''));
        $repoPath = trim((string) ($repository['repo_url'] ?? ''));

        $path = '';
        if ($indexPath !== '') {
            $path = $indexPath;
        } elseif ($repoPath !== '') {
            $path = rtrim($repoPath, '/') . '/catmin-repository.json';
        }
        if ($path === '') {
            return [
                'ok' => false,
                'status' => 'invalid',
                'message' => 'Chemin local index requis.',
                'module_count' => 0,
                'errors' => ['local_index_missing'],
            ];
        }
        if (!str_starts_with($path, '/')) {
            $path = CATMIN_ROOT . '/' . ltrim($path, '/');
        }
        $real = realpath($path);
        if (!is_string($real) || !is_file($real)) {
            return [
                'ok' => false,
                'status' => 'error',
                'message' => 'Index local introuvable.',
                'module_count' => 0,
                'errors' => ['local_index_unreachable'],
            ];
        }

        $raw = @file_get_contents($real);
        $decoded = is_string($raw) ? json_decode($raw, true) : null;
        if (!is_array($decoded)) {
            return [
                'ok' => false,
                'status' => 'invalid',
                'message' => 'Index local JSON invalide.',
                'module_count' => 0,
                'errors' => ['local_index_json_invalid'],
            ];
        }

        $parsed = (new CoreModuleRepositoryIndexStandard())->parse($decoded);
        if (!(bool) ($parsed['ok'] ?? false)) {
            return [
                'ok' => false,
                'status' => 'invalid',
                'message' => 'Index local non conforme CATMIN.',
                'module_count' => 0,
                'errors' => ['local_standard_index_invalid'],
            ];
        }
        $items = is_array($parsed['items'] ?? null) ? $parsed['items'] : [];
        return [
            'ok' => true,
            'status' => 'ok',
            'message' => 'Index local miroir valide.',
            'module_count' => count($items),
            'errors' => [],
        ];
    }

    private function checkGithub(array $repository): array
    {
        $repo = $this->extractGithubRepo($repository);
        $branch = trim((string) ($repository['branch_or_channel'] ?? 'main'));
        if ($repo === null) {
            return [
                'ok' => false,
                'status' => 'invalid',
                'message' => 'Repository GitHub invalide.',
                'module_count' => 0,
                'errors' => ['github_repo_invalid'],
            ];
        }

        $catalog = (new CoreMarketGithub($repo, $branch))->catalog();
        if (!(bool) ($catalog['ok'] ?? false)) {
            return [
                'ok' => false,
                'status' => 'error',
                'message' => (string) ($catalog['error'] ?? 'Lecture impossible.'),
                'module_count' => 0,
                'errors' => ['catalog_unreachable'],
            ];
        }

        $items = is_array($catalog['items'] ?? null) ? $catalog['items'] : [];
        if ((bool) ($repository['requires_manifest_standard'] ?? true) && !((bool) ($catalog['standard_index'] ?? false))) {
            return [
                'ok' => false,
                'status' => 'invalid',
                'message' => 'Dépôt non standard: catmin-repository.json requis.',
                'module_count' => 0,
                'errors' => ['standard_index_required'],
            ];
        }

        return [
            'ok' => true,
            'status' => 'ok',
            'message' => 'Catalogue lisible.',
            'module_count' => count($items),
            'errors' => [],
        ];
    }

    private function checkCustomIndex(array $repository): array
    {
        $indexUrl = trim((string) ($repository['index_url'] ?? ''));
        if ($indexUrl === '' || filter_var($indexUrl, FILTER_VALIDATE_URL) === false) {
            return [
                'ok' => false,
                'status' => 'invalid',
                'message' => 'Index URL invalide.',
                'module_count' => 0,
                'errors' => ['index_url_invalid'],
            ];
        }

        $raw = $this->requestRaw($indexUrl);
        if (!is_string($raw) || $raw === '') {
            return [
                'ok' => false,
                'status' => 'error',
                'message' => 'Index inaccessible.',
                'module_count' => 0,
                'errors' => ['index_unreachable'],
            ];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [
                'ok' => false,
                'status' => 'invalid',
                'message' => 'Index JSON invalide.',
                'module_count' => 0,
                'errors' => ['index_json_invalid'],
            ];
        }

        $parsed = (new CoreModuleRepositoryIndexStandard())->parse($decoded);
        if ((bool) ($parsed['ok'] ?? false)) {
            $items = is_array($parsed['items'] ?? null) ? $parsed['items'] : [];
            return [
                'ok' => true,
                'status' => 'ok',
                'message' => 'Index standard CATMIN valide.',
                'module_count' => count($items),
                'errors' => [],
            ];
        }

        if ((bool) ($repository['requires_manifest_standard'] ?? true)) {
            return [
                'ok' => false,
                'status' => 'invalid',
                'message' => 'Index non conforme au standard CATMIN.',
                'module_count' => 0,
                'errors' => ['standard_index_invalid'],
            ];
        }

        $items = is_array($decoded['items'] ?? null) ? $decoded['items'] : [];

        return [
            'ok' => true,
            'status' => 'ok',
            'message' => 'Index lisible.',
            'module_count' => count($items),
            'errors' => [],
        ];
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
                CURLOPT_HTTPHEADER => ['User-Agent: CATMIN-Market-Registry'],
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
