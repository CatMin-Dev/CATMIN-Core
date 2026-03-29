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
        if (!is_dir(base_path('.git'))) {
            return ['', '', false];
        }

        $git = $this->resolveGitBinary();

        $revision = $this->runGit([$git, 'rev-parse', '--short', 'HEAD']);
        $branch = $this->runGit([$git, 'rev-parse', '--abbrev-ref', 'HEAD']);
        $status = $this->runGit([$git, 'status', '--porcelain']);

        return [$revision, $branch, $status !== ''];
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