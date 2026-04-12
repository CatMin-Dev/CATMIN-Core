<?php

declare(strict_types=1);

final class CoreUpdaterGithub
{
    private string $repo;
    private string $apiBase;

    public function __construct(?string $repo = null)
    {
        $repo = $repo !== null ? trim($repo) : trim((string) env('CATMIN_PUBLIC_CORE_REPO', 'CatMin-Dev/CATMIN-Core'));
        $this->repo = $repo !== '' ? $repo : 'CatMin-Dev/CATMIN-Core';
        $this->apiBase = 'https://api.github.com/repos/' . $this->repo;
    }

    public function latestRelease(): array
    {
        $payload = $this->requestJson($this->apiBase . '/releases/latest');
        if (!is_array($payload)) {
            return ['ok' => false, 'error' => 'Impossible de lire la release distante.', 'release' => null];
        }

        return [
            'ok' => true,
            'error' => '',
            'release' => [
                'tag' => (string) ($payload['tag_name'] ?? ''),
                'name' => (string) ($payload['name'] ?? ''),
                'body' => (string) ($payload['body'] ?? ''),
                'published_at' => (string) ($payload['published_at'] ?? ''),
                'assets' => is_array($payload['assets'] ?? null) ? $payload['assets'] : [],
            ],
        ];
    }

    public function latestTag(): array
    {
        $payload = $this->requestJson($this->apiBase . '/tags?per_page=100');
        if (!is_array($payload)) {
            return ['ok' => false, 'error' => 'Impossible de lire les tags distants.', 'tag' => ''];
        }

        $bestTag = '';
        foreach ($payload as $row) {
            if (!is_array($row)) {
                continue;
            }
            $tag = trim((string) ($row['name'] ?? ''));
            if ($tag === '') {
                continue;
            }

            $normalized = $this->normalizeTag($tag);
            if ($normalized === '') {
                continue;
            }

            if ($bestTag === '' || version_compare($normalized, $bestTag, '>')) {
                $bestTag = $normalized;
            }
        }

        if ($bestTag === '') {
            return ['ok' => false, 'error' => 'Aucun tag distant exploitable.', 'tag' => ''];
        }

        return ['ok' => true, 'error' => '', 'tag' => $bestTag];
    }

    public function findStandaloneAsset(array $release): ?array
    {
        $assets = is_array($release['assets'] ?? null) ? $release['assets'] : [];
        foreach ($assets as $asset) {
            if (!is_array($asset)) {
                continue;
            }
            $name = (string) ($asset['name'] ?? '');
            $url = (string) ($asset['browser_download_url'] ?? '');
            if ($name === '' || $url === '') {
                continue;
            }
            if (preg_match('/catmin-.*-standalone\.zip$/i', $name) === 1) {
                return [
                    'name' => $name,
                    'url' => $url,
                    'size' => (int) ($asset['size'] ?? 0),
                ];
            }
        }
        return null;
    }

    public function downloadAsset(string $url, string $destination): array
    {
        $url = trim($url);
        if ($url === '') {
            return ['ok' => false, 'error' => 'URL asset vide.', 'path' => ''];
        }

        $body = $this->requestRaw($url);
        if (!is_string($body) || $body === '') {
            return ['ok' => false, 'error' => 'Téléchargement ZIP impossible.', 'path' => ''];
        }

        $dir = dirname($destination);
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            return ['ok' => false, 'error' => 'Dossier downloads non writable.', 'path' => ''];
        }

        if (@file_put_contents($destination, $body, LOCK_EX) === false) {
            return ['ok' => false, 'error' => 'Écriture ZIP impossible.', 'path' => ''];
        }

        return ['ok' => true, 'error' => '', 'path' => $destination];
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

    private function normalizeTag(string $tag): string
    {
        $tag = trim($tag);
        if ($tag === '') {
            return '';
        }
        if (str_starts_with(strtolower($tag), 'v')) {
            $tag = substr($tag, 1);
        }
        return trim($tag);
    }

    private function requestRaw(string $url): ?string
    {
        $headers = [
            'User-Agent: CATMIN-Updater',
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
