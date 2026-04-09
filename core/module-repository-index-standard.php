<?php

declare(strict_types=1);

final class CoreModuleRepositoryIndexStandard
{
    /** @return array{ok:bool,errors:array<int,string>,repository:array<string,mixed>,items:array<int,array<string,mixed>>} */
    public function parse(array $payload): array
    {
        $errors = [];

        foreach (['schema_version', 'repository_name', 'repository_slug', 'owner', 'provider', 'base_repo_url', 'trust_claim', 'modules'] as $field) {
            if (!array_key_exists($field, $payload)) {
                $errors[] = 'Index field missing: ' . $field;
            }
        }

        $schemaVersion = trim((string) ($payload['schema_version'] ?? ''));
        if ($schemaVersion === '' || preg_match('/^1\.[0-9]+\.[0-9]+$/', $schemaVersion) !== 1) {
            $errors[] = 'Schema version unsupported';
        }

        $trustClaim = strtolower(trim((string) ($payload['trust_claim'] ?? 'community')));
        if (!in_array($trustClaim, ['official', 'trusted', 'community'], true)) {
            $errors[] = 'trust_claim invalid';
        }

        $modules = is_array($payload['modules'] ?? null) ? $payload['modules'] : [];
        if ($modules === []) {
            $errors[] = 'No module declared in index';
        }

        $items = [];
        foreach ($modules as $row) {
            if (!is_array($row)) {
                continue;
            }
            $item = $this->normalizeModule($row);
            if ($item['errors'] !== []) {
                foreach ($item['errors'] as $moduleError) {
                    $errors[] = $moduleError;
                }
                continue;
            }
            $items[] = (array) ($item['module'] ?? []);
        }

        $repository = [
            'schema_version' => $schemaVersion,
            'repository_name' => trim((string) ($payload['repository_name'] ?? '')),
            'repository_slug' => strtolower(trim((string) ($payload['repository_slug'] ?? ''))),
            'owner' => trim((string) ($payload['owner'] ?? '')),
            'provider' => strtolower(trim((string) ($payload['provider'] ?? ''))),
            'base_repo_url' => trim((string) ($payload['base_repo_url'] ?? '')),
            'homepage' => trim((string) ($payload['homepage'] ?? '')),
            'support_url' => trim((string) ($payload['support_url'] ?? '')),
            'trust_claim' => $trustClaim,
            'default_release_channel' => strtolower(trim((string) ($payload['default_release_channel'] ?? 'stable'))),
        ];

        if ($repository['repository_slug'] === '' || preg_match('/^[a-z0-9][a-z0-9\-]{1,118}[a-z0-9]$/', $repository['repository_slug']) !== 1) {
            $errors[] = 'repository_slug invalid';
        }
        if ($repository['provider'] === '' || !in_array($repository['provider'], ['github', 'custom_http_index', 'local_mirror'], true)) {
            $errors[] = 'provider invalid';
        }
        if ($repository['provider'] === 'local_mirror') {
            if ($repository['base_repo_url'] === '') {
                $errors[] = 'base_repo_url invalid';
            }
        } elseif ($repository['base_repo_url'] === '' || filter_var($repository['base_repo_url'], FILTER_VALIDATE_URL) === false) {
            $errors[] = 'base_repo_url invalid';
        }

        return [
            'ok' => $errors === [],
            'errors' => array_values(array_unique($errors)),
            'repository' => $repository,
            'items' => $items,
        ];
    }

    /** @return array{errors:array<int,string>,module:array<string,mixed>} */
    private function normalizeModule(array $row): array
    {
        $errors = [];
        foreach (['slug', 'name', 'type', 'version', 'catmin_min', 'php_min', 'manifest_url', 'release_zip_url'] as $field) {
            if (trim((string) ($row[$field] ?? '')) === '') {
                $errors[] = 'Module field missing: ' . $field;
            }
        }

        $slug = strtolower(trim((string) ($row['slug'] ?? '')));
        if ($slug === '' || preg_match('/^[a-z0-9][a-z0-9\-]*$/', $slug) !== 1) {
            $errors[] = 'Module slug invalid: ' . $slug;
        }

        $scope = strtolower(trim((string) ($row['type'] ?? 'admin')));
        if (!in_array($scope, ['core', 'admin', 'front', 'integration', 'driver'], true)) {
            $errors[] = 'Module type invalid: ' . $scope;
        }

        $version = trim((string) ($row['version'] ?? ''));
        if ($version === '' || preg_match('/^[0-9]+\.[0-9]+\.[0-9]+(?:[-+][a-zA-Z0-9.-]+)?$/', $version) !== 1) {
            $errors[] = 'Module version invalid: ' . $slug;
        }

        $releaseZipUrl = trim((string) ($row['release_zip_url'] ?? ''));
        $manifestUrl = trim((string) ($row['manifest_url'] ?? ''));
        if ($releaseZipUrl !== '' && filter_var($releaseZipUrl, FILTER_VALIDATE_URL) === false) {
            $errors[] = 'release_zip_url invalid: ' . $slug;
        }
        if ($manifestUrl !== '' && filter_var($manifestUrl, FILTER_VALIDATE_URL) === false) {
            $errors[] = 'manifest_url invalid: ' . $slug;
        }

        $releaseChannel = strtolower(trim((string) ($row['release_channel'] ?? 'stable')));
        if (!in_array($releaseChannel, ['stable', 'beta', 'alpha', 'experimental'], true)) {
            $releaseChannel = 'stable';
        }

        $lifecycle = strtolower(trim((string) ($row['lifecycle_status'] ?? 'active')));
        if (!in_array($lifecycle, ['active', 'deprecated', 'abandoned', 'replaced', 'archived', 'experimental'], true)) {
            $lifecycle = 'active';
        }

        $module = [
            'scope' => $scope,
            'slug' => $slug,
            'name' => trim((string) ($row['name'] ?? strtoupper($slug))),
            'description' => trim((string) ($row['description'] ?? '')),
            'version' => $version,
            'catmin_min' => trim((string) ($row['catmin_min'] ?? '')),
            'catmin_max' => trim((string) ($row['catmin_max'] ?? '')),
            'php_min' => trim((string) ($row['php_min'] ?? '')),
            'manifest_url' => $manifestUrl,
            'zip_url' => $releaseZipUrl,
            'checksums_url' => trim((string) ($row['checksums_url'] ?? '')),
            'signature_url' => trim((string) ($row['signature_url'] ?? '')),
            'readme_url' => trim((string) ($row['readme_url'] ?? '')),
            'changelog_url' => trim((string) ($row['changelog_url'] ?? '')),
            'integrity_ready' => (bool) ($row['integrity_ready'] ?? false),
            'signature_ready' => (bool) ($row['signature_ready'] ?? false),
            'release_channel' => $releaseChannel,
            'lifecycle_status' => $lifecycle,
            'replacement_slug' => strtolower(trim((string) ($row['replacement_slug'] ?? ''))),
            'deprecation_message' => trim((string) ($row['deprecation_message'] ?? '')),
        ];

        $manifestRaw = $this->requestRaw($manifestUrl);
        $manifestDecoded = is_string($manifestRaw) ? json_decode($manifestRaw, true) : null;
        $module['manifest'] = is_array($manifestDecoded) ? $manifestDecoded : [];

        return ['errors' => $errors, 'module' => $module];
    }

    private function requestRaw(string $url): ?string
    {
        if ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return null;
        }
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            if ($ch === false) {
                return null;
            }
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CONNECTTIMEOUT => 8,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_HTTPHEADER => ['User-Agent: CATMIN-Market-Index'],
            ]);
            $body = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            curl_close($ch);
            if (!is_string($body) || $code < 200 || $code >= 300) {
                return null;
            }
            return $body;
        }

        $raw = @file_get_contents($url);
        return is_string($raw) ? $raw : null;
    }
}
