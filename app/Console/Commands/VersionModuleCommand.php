<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ModuleVersionManager;

class VersionModuleCommand extends Command
{
    protected $signature = 'module:version 
                            {action : increment|set|show|matrix}
                            {module? : Module slug (e.g., shop)}
                            {--type=patch : Increment type (patch|minor|major)}
                            {--to= : Target version for set action (e.g., 2.0.0)}
                            {--tag= : Beta tag (e.g., dev, beta1)}';

    protected $description = 'Manage module and dashboard versions during development';

    public function handle(): int
    {
        $action = $this->argument('action');
        $module = $this->argument('module');

        return match ($action) {
            'increment' => $this->handleIncrement($module),
            'set' => $this->handleSet($module),
            'show' => $this->handleShow($module),
            'matrix' => $this->handleMatrix(),
            default => $this->error("Unknown action: {$action}") || 1,
        };
    }

    private function handleIncrement(?string $module): int
    {
        if (!$module) {
            $this->error('Module slug required for increment action');

            return 1;
        }

        $type = $this->option('type') ?? 'patch';
        $tag = $this->option('tag') ?? (config('app.development_phase') === 'v2-dev' ? 'dev' : null);

        $newVersion = ModuleVersionManager::increment($module, $type, $tag);

        if ($newVersion) {
            $this->info("✓ Module '{$module}' incremented to: {$newVersion}");

            return 0;
        }

        $this->error("✗ Failed to increment module '{$module}'");

        return 1;
    }

    private function handleSet(?string $module): int
    {
        if (!$module) {
            $this->error('Module slug required for set action');

            return 1;
        }

        $version = $this->option('to');

        if (!$version) {
            $this->error('Target version required (use --to)');

            return 1;
        }

        if (ModuleVersionManager::set($module, $version)) {
            $this->info("✓ Module '{$module}' set to: {$version}");

            return 0;
        }

        $this->error("✗ Failed to set module '{$module}' to {$version}");

        return 1;
    }

    private function handleShow(?string $module): int
    {
        if ($module) {
            $version = ModuleVersionManager::getVersion($module);

            if ($version) {
                $this->info("{$module}: {$version}");

                return 0;
            }

            $this->error("Module '{$module}' not found");

            return 1;
        }

        // Show all versions
        $versions = ModuleVersionManager::getAllVersions();

        if (empty($versions)) {
            $this->info('No modules found');

            return 0;
        }

        $this->line('<fg=blue>═══════════════════════════════════</>');
        $this->line('<fg=blue>Module Version Report</>');
        $this->line('<fg=blue>═══════════════════════════════════</>');

        foreach ($versions as $moduleSlug => $version) {
            $this->line(sprintf('  %-30s %s', $moduleSlug, $version));
        }

        $this->line('');
        $this->line(sprintf('  Dashboard: %s', ModuleVersionManager::getDashboardVersion()));
        $this->line('<fg=blue>═══════════════════════════════════</>');

        return 0;
    }

    private function handleMatrix(): int
    {
        $matrix = ModuleVersionManager::generateMatrix();

        $this->line('<fg=blue>═══════════════════════════════════</>');
        $this->line('<fg=blue>Version Matrix Report</>');
        $this->line(sprintf('<fg=green>Generated: %s</>', $matrix['generated_at']));
        $this->line(sprintf('<fg=green>Phase: %s</>', $matrix['development_phase']));
        $this->line('<fg=blue>═══════════════════════════════════</>');
        $this->line('');
        $this->line(sprintf('Dashboard: <fg=yellow>%s</>', $matrix['dashboard_version']));
        $this->line('');
        $this->line('Modules:');

        foreach ($matrix['modules'] as $slug => $version) {
            $tag = str_contains($version, '-') ? '<fg=cyan>' : '<fg=green>';
            $this->line(sprintf('  %-30s %s%s</>', $slug, $tag, $version));
        }

        $this->line('');
        $this->line(sprintf('<fg=magenta>Total: %d modules</>', $matrix['total_modules']));
        $this->line('<fg=blue>═══════════════════════════════════</>');

        return 0;
    }
}
