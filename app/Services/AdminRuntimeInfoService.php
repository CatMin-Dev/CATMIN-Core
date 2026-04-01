<?php

namespace App\Services;

use Symfony\Component\Process\Process;

class AdminRuntimeInfoService
{
    /** @var array<string, mixed>|null */
    private ?array $cache = null;

    /**
     * @return array<string, mixed>
     */
    public function get(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        $dashboardVersion = (string) config('app.dashboard_version', 'V3-dev');
        $developmentPhase = (string) config('app.development_phase', 'v3-dev');
        $expectedDashboardVersion = $this->expectedDashboardVersion($developmentPhase);

        [$revision, $branch, $isDirty] = $this->gitState();

        return $this->cache = [
            'dashboard_version' => $dashboardVersion,
            'development_phase' => $developmentPhase,
            'expected_dashboard_version' => $expectedDashboardVersion,
            'dashboard_is_up_to_date' => strcasecmp($dashboardVersion, $expectedDashboardVersion) === 0,
            'revision' => $revision !== '' ? $revision : 'n/a',
            'branch' => $branch !== '' ? $branch : 'n/a',
            'is_dirty' => $isDirty,
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION,
            'environment' => app()->environment(),
            'admin_path' => (string) config('catmin.admin.path', 'admin'),
        ];
    }

    private function expectedDashboardVersion(string $phase): string
    {
        $normalized = trim($phase);

        if (preg_match('/^v(\d+)(?:\.5)?-dev$/i', $normalized, $matches) === 1) {
            return 'V' . $matches[1] . '-dev';
        }

        return (string) config('app.dashboard_version', 'V3-dev');
    }

    /**
     * @return array{0:string,1:string,2:bool}
     */
    private function gitState(): array
    {
        $gitDir = $this->resolveGitDirectory();

        if ($gitDir === null) {
            return ['', '', false];
        }

        $git = $this->resolveGitBinary();

        $revision = $this->runGit([$git, 'rev-parse', '--short', 'HEAD']);
        $branch = $this->runGit([$git, 'rev-parse', '--abbrev-ref', 'HEAD']);
        $status = $this->runGit([$git, 'status', '--porcelain']);

        if ($revision === '' || $branch === '') {
            [$fallbackRevision, $fallbackBranch] = $this->readGitHead($gitDir);
            $revision = $revision !== '' ? $revision : $fallbackRevision;
            $branch = $branch !== '' ? $branch : $fallbackBranch;
        }

        return [$revision, $branch, $status !== ''];
    }

    private function resolveGitDirectory(): ?string
    {
        $gitPath = base_path('.git');

        if (is_dir($gitPath)) {
            return $gitPath;
        }

        if (!is_file($gitPath)) {
            return null;
        }

        $contents = trim((string) @file_get_contents($gitPath));

        if (!str_starts_with($contents, 'gitdir:')) {
            return null;
        }

        $relativePath = trim(substr($contents, 7));
        $resolved = str_starts_with($relativePath, '/')
            ? $relativePath
            : base_path($relativePath);

        return is_dir($resolved) ? $resolved : null;
    }

    /**
     * @return array{0:string,1:string}
     */
    private function readGitHead(string $gitDir): array
    {
        $headFile = $gitDir . '/HEAD';

        if (!is_file($headFile)) {
            return ['', ''];
        }

        $head = trim((string) @file_get_contents($headFile));

        if ($head === '') {
            return ['', ''];
        }

        if (!str_starts_with($head, 'ref: ')) {
            return [substr($head, 0, 7), 'detached'];
        }

        $ref = trim(substr($head, 5));
        $branch = basename($ref);
        $hash = $this->readGitRefHash($gitDir, $ref);

        return [$hash !== '' ? substr($hash, 0, 7) : '', $branch];
    }

    private function readGitRefHash(string $gitDir, string $ref): string
    {
        $refFile = $gitDir . '/' . $ref;

        if (is_file($refFile)) {
            return trim((string) @file_get_contents($refFile));
        }

        $packedRefs = $gitDir . '/packed-refs';

        if (!is_file($packedRefs)) {
            return '';
        }

        $lines = @file($packedRefs, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if (!is_array($lines)) {
            return '';
        }

        foreach ($lines as $line) {
            if ($line === '' || str_starts_with($line, '#') || str_starts_with($line, '^')) {
                continue;
            }

            [$hash, $lineRef] = array_pad(preg_split('/\s+/', trim($line), 2) ?: [], 2, '');

            if ($lineRef === $ref) {
                return $hash;
            }
        }

        return '';
    }

    private function resolveGitBinary(): string
    {
        foreach (['/usr/bin/git', '/usr/local/bin/git', '/opt/homebrew/bin/git'] as $candidate) {
            if (is_executable($candidate)) {
                return $candidate;
            }
        }

        return 'git';
    }

    /**
     * @param array<int, string> $command
     */
    private function runGit(array $command): string
    {
        try {
            $process = new Process($command, base_path(), null, null, 3.0);
            $process->run();

            if (!$process->isSuccessful()) {
                return '';
            }

            return trim((string) $process->getOutput());
        } catch (\Throwable) {
            return '';
        }
    }
}