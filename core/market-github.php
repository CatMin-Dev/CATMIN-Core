<?php

declare(strict_types=1);

final class CoreMarketGithub
{
    private string $repo;
    private string $apiBase;

    public function __construct(?string $repo = null)
    {
        $repo = $repo !== null ? trim($repo) : trim((string) env('CATMIN_PUBLIC_MODULES_REPO', 'CatMin-Dev/CATMIN-Modules'));
        $this->repo = $repo !== '' ? $repo : 'CatMin-Dev/CATMIN-Modules';
        $this->apiBase = 'https://api.github.com/repos/' . $this->repo;
    }

    public function catalog(): array
    {
        $types = $this->contents('/modules');
        if (!is_array($types)) {
            return ['ok' => false, 'error' => 'Lecture catalogue modules impossible.', 'items' => []];
        }

        $items = [];
        foreach ($types as $typeNode) {
            if (!is_array($typeNode) || (string) ($typeNode['type'] ?? '') !== 'dir') {
                continue;
            }
            $scope = trim((string) ($typeNode['name'] ?? ''));
            if ($scope === '') {
                continue;
            }

            $modules = $this->contents('/modules/' . rawurlencode($scope));
            if (!is_array($modules)) {
                continue;
            }

            foreach ($modules as $moduleNode) {
                if (!is_array($moduleNode) || (string) ($moduleNode['type'] ?? '') !== 'dir') {
                    continue;
                }
                $slug = trim((string) ($moduleNode['name'] ?? ''));
                if ($slug === '') {
                    continue;
                }

                $manifestMeta = $this->contentMeta('/modules/' . rawurlencode($scope) . '/' . rawurlencode($slug) . '/manifest.json');
                $manifest = is_array($manifestMeta) ? $this->readJsonFromDownloadUrl((string) ($manifestMeta['download_url'] ?? '')) : null;
                if (!is_array($manifest)) {
                    continue;
                }

                $zipUrl = 'https://codeload.github.com/' . $this->repo . '/zip/refs/heads/main';
                $items[] = [
                    'scope' => $scope,
                    'slug' => (string) ($manifest['slug'] ?? $slug),
                    'name' => (string) ($manifest['name'] ?? strtoupper($slug)),
                    'description' => (string) ($manifest['description'] ?? ''),
                    'version' => (string) ($manifest['version'] ?? '0.0.0'),
                    'catmin_min' => (string) ($manifest['catmin_min'] ?? ''),
                    'catmin_max' => (string) ($manifest['catmin_max'] ?? ''),
                    'manifest' => $manifest,
                    'zip_url' => $zipUrl,
                    'path_in_zip' => 'CATMIN-Modules-main/modules/' . $scope . '/' . $slug,
                ];
            }
        }

        usort($items, static fn (array $a, array $b): int => strcmp((string) ($a['scope'] . '/' . $a['slug']), (string) ($b['scope'] . '/' . $b['slug'])));
        return ['ok' => true, 'error' => '', 'items' => $items];
    }

    private function contents(string $path): ?array
    {
        return $this->requestJson($this->apiBase . '/contents' . $path);
    }

    private function contentMeta(string $path): ?array
    {
        $meta = $this->requestJson($this->apiBase . '/contents' . $path);
        return is_array($meta) ? $meta : null;
    }

    private function readJsonFromDownloadUrl(string $url): ?array
    {
        $raw = $this->requestRaw($url);
        if (!is_string($raw) || $raw === '') {
            return null;
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : null;
    }

    private function requestJson(string $url): ?array
    {
        $raw = $this->requestRaw($url);
        if (!is_string($raw) || $raw === '') {
            return null;
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : null;
    }

    private function requestRaw(string $url): ?string
    {
        $headers = [
            'User-Agent: CATMIN-Market',
            'Accept: application/vnd.github+json',
        ];

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            if ($ch === false) {
                return null;
            }
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_TIMEOUT => 45,
                CURLOPT_HTTPHEADER => $headers,
            ]);
            $body = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            curl_close($ch);
            if (!is_string($body) || $code < 200 || $code >= 300) {
                return null;
            }
            return $body;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => implode("\r\n", $headers),
                'timeout' => 45,
            ],
        ]);

        $body = @file_get_contents($url, false, $context);
        return is_string($body) ? $body : null;
    }
}
