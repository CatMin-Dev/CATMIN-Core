<?php

declare(strict_types=1);

final class CoreModuleChecksumValidator
{
    public function validate(string $modulePath, string $checksumsRelativePath = ''): array
    {
        $checksumsPath = $this->resolveChecksumsPath($modulePath, $checksumsRelativePath);
        if (!is_file($checksumsPath)) {
            return [
                'status' => 'missing_checksums',
                'valid' => false,
                'errors' => ['checksums.json manquant'],
                'checked_files' => 0,
                'mismatches' => [],
                'module_hash_ok' => false,
            ];
        }

        $decoded = json_decode((string) file_get_contents($checksumsPath), true);
        if (!is_array($decoded)) {
            return [
                'status' => 'unsupported_schema',
                'valid' => false,
                'errors' => ['checksums.json invalide'],
                'checked_files' => 0,
                'mismatches' => [],
                'module_hash_ok' => false,
            ];
        }

        $algo = strtolower(trim((string) ($decoded['algorithm'] ?? 'sha256')));
        if ($algo !== 'sha256') {
            return [
                'status' => 'unsupported_schema',
                'valid' => false,
                'errors' => ['Algorithme non supporte: ' . $algo],
                'checked_files' => 0,
                'mismatches' => [],
                'module_hash_ok' => false,
            ];
        }

        $files = is_array($decoded['files'] ?? null) ? $decoded['files'] : [];
        if ($files === []) {
            return [
                'status' => 'unsupported_schema',
                'valid' => false,
                'errors' => ['Aucun hash fichier dans checksums.json'],
                'checked_files' => 0,
                'mismatches' => [],
                'module_hash_ok' => false,
            ];
        }

        $mismatches = [];
        $pairs = [];
        foreach ($files as $relative => $expectedHash) {
            $relative = ltrim(str_replace('\\', '/', (string) $relative), '/');
            $expectedHash = strtolower(trim((string) $expectedHash));
            if ($relative === '' || $expectedHash === '') {
                continue;
            }
            $full = rtrim($modulePath, '/') . '/' . $relative;
            if (!is_file($full)) {
                $mismatches[] = ['file' => $relative, 'reason' => 'missing'];
                continue;
            }
            $actual = strtolower((string) hash_file('sha256', $full));
            if (!hash_equals($expectedHash, $actual)) {
                $mismatches[] = ['file' => $relative, 'reason' => 'hash_mismatch'];
            }
            $pairs[] = $relative . ':' . $expectedHash;
        }

        sort($pairs);
        $computedModuleHash = hash('sha256', implode("\n", $pairs));
        $expectedModuleHash = strtolower(trim((string) ($decoded['module_hash'] ?? '')));
        $moduleHashOk = ($expectedModuleHash !== '') && hash_equals($expectedModuleHash, $computedModuleHash);

        $valid = $mismatches === [] && $moduleHashOk;
        $status = $valid ? 'valid' : (($mismatches !== []) ? 'tampered' : 'invalid');

        return [
            'status' => $status,
            'valid' => $valid,
            'errors' => $valid ? [] : ['Verification integrity en echec'],
            'checked_files' => count($files),
            'mismatches' => $mismatches,
            'module_hash_ok' => $moduleHashOk,
            'module_hash' => $computedModuleHash,
            'checksums' => $decoded,
        ];
    }

    private function resolveChecksumsPath(string $modulePath, string $checksumsRelativePath): string
    {
        $moduleRoot = rtrim($modulePath, '/');
        $candidate = trim($checksumsRelativePath);
        if ($candidate !== '' && $this->isSafeRelativePath($candidate)) {
            $resolved = $moduleRoot . '/' . ltrim(str_replace('\\', '/', $candidate), '/');
            if (is_file($resolved)) {
                return $resolved;
            }
        }

        return $moduleRoot . '/checksums.json';
    }

    private function isSafeRelativePath(string $path): bool
    {
        $path = trim($path);
        if ($path === '' || str_contains($path, "\0")) {
            return false;
        }

        if (str_starts_with($path, '/') || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1) {
            return false;
        }

        foreach (explode('/', str_replace('\\', '/', $path)) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                return false;
            }
        }

        return true;
    }
}

