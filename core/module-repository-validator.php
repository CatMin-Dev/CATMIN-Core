<?php

declare(strict_types=1);

final class CoreModuleRepositoryValidator
{
    /** @return array{ok:bool,errors:array<int,string>,data:array<string,mixed>} */
    public function validate(array $payload): array
    {
        $name = trim((string) ($payload['name'] ?? ''));
        $slug = strtolower(trim((string) ($payload['slug'] ?? '')));
        $provider = strtolower(trim((string) ($payload['provider'] ?? 'github')));
        $repoUrl = trim((string) ($payload['repo_url'] ?? ''));
        $apiUrl = trim((string) ($payload['api_url'] ?? ''));
        $indexUrl = trim((string) ($payload['index_url'] ?? ''));
        $branch = trim((string) ($payload['branch_or_channel'] ?? 'main'));
        $trust = strtolower(trim((string) ($payload['trust_level'] ?? 'community')));
        $notes = trim((string) ($payload['notes'] ?? ''));
        $allowedChannels = trim((string) ($payload['allowed_release_channels'] ?? 'stable,beta,dev'));

        $errors = [];

        if ($name === '') {
            $errors[] = 'Nom du dépôt requis.';
        }

        if ($slug === '' || preg_match('/^[a-z0-9][a-z0-9\-]{1,118}[a-z0-9]$/', $slug) !== 1) {
            $errors[] = 'Slug invalide (a-z, 0-9, tiret).';
        }

        if (!in_array($provider, ['github', 'custom_http_index', 'local_mirror'], true)) {
            $errors[] = 'Provider invalide.';
        }

        if ($repoUrl === '') {
            $errors[] = 'URL dépôt requise.';
        }

        if ($provider === 'github') {
            if (!$this->looksLikeGithubRepo($repoUrl)) {
                $errors[] = 'URL dépôt GitHub invalide.';
            }
        }

        if ($provider === 'custom_http_index') {
            if ($indexUrl === '' || filter_var($indexUrl, FILTER_VALIDATE_URL) === false) {
                $errors[] = 'Index URL requis et valide pour custom_http_index.';
            }
        }

        if (!in_array($trust, ['official', 'trusted', 'community', 'blocked'], true)) {
            $errors[] = 'Niveau de confiance invalide.';
        }

        if ($branch === '') {
            $errors[] = 'Branche / channel requis.';
        }

        $channels = $this->normalizeChannels($allowedChannels);
        if ($channels === []) {
            $errors[] = 'Canaux autorisés invalides.';
        }

        return [
            'ok' => $errors === [],
            'errors' => $errors,
            'data' => [
                'name' => mb_substr($name, 0, 160),
                'slug' => mb_substr($slug, 0, 120),
                'provider' => $provider,
                'repo_url' => mb_substr($repoUrl, 0, 255),
                'api_url' => $apiUrl !== '' ? mb_substr($apiUrl, 0, 255) : null,
                'index_url' => $indexUrl !== '' ? mb_substr($indexUrl, 0, 255) : null,
                'branch_or_channel' => mb_substr($branch, 0, 80),
                'trust_level' => $trust,
                'is_official' => ((string) ($payload['is_official'] ?? '0')) === '1' || $trust === 'official',
                'is_enabled' => ((string) ($payload['is_enabled'] ?? '0')) === '1',
                'requires_signature' => ((string) ($payload['requires_signature'] ?? '0')) === '1',
                'requires_checksums' => ((string) ($payload['requires_checksums'] ?? '0')) === '1',
                'requires_manifest_standard' => ((string) ($payload['requires_manifest_standard'] ?? '1')) === '1',
                'allowed_release_channels' => implode(',', $channels),
                'notes' => $notes !== '' ? mb_substr($notes, 0, 4000) : null,
            ],
        ];
    }

    /** @return array<int,string> */
    private function normalizeChannels(string $raw): array
    {
        $parts = preg_split('/[,\s]+/', strtolower($raw)) ?: [];
        $parts = array_values(array_unique(array_filter(array_map('trim', $parts), static fn (string $v): bool => $v !== '')));
        $allowed = ['stable', 'beta', 'dev', 'alpha', 'experimental'];

        return array_values(array_filter($parts, static fn (string $v): bool => in_array($v, $allowed, true)));
    }

    private function looksLikeGithubRepo(string $value): bool
    {
        $value = trim($value);
        if (preg_match('#^[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+$#', $value) === 1) {
            return true;
        }
        if (filter_var($value, FILTER_VALIDATE_URL) !== false) {
            $host = strtolower((string) parse_url($value, PHP_URL_HOST));
            return in_array($host, ['github.com', 'www.github.com', 'api.github.com'], true);
        }

        return false;
    }
}
