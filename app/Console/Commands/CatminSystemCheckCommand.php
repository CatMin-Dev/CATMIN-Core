<?php

namespace App\Console\Commands;

use App\Services\AddonManager;
use App\Services\MigrationCollisionService;
use App\Services\ModuleManager;
use Illuminate\Console\Command;

class CatminSystemCheckCommand extends Command
{
    protected $signature = 'catmin:system:check {--json : sortie JSON}';

    protected $description = 'Verifier l\'etat global CATMIN (modules, addons, collisions migrations, dossier storage)';

    public function handle(): int
    {
        $report = [
            'modules' => ModuleManager::summary(),
            'addons' => AddonManager::summary(),
            'module_state_issues' => ModuleManager::stateIssues(),
            'migration_collisions' => MigrationCollisionService::detectBasenameCollisions(),
            'storage_writable' => is_writable(storage_path()),
            'cache_writable' => is_writable(storage_path('framework/cache')),
        ];

        if ((bool) $this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            return empty($report['module_state_issues']) && empty($report['migration_collisions']) ? self::SUCCESS : self::FAILURE;
        }

        $this->info('CATMIN system check');
        $this->line('- Modules: ' . $report['modules']['enabled'] . '/' . $report['modules']['total'] . ' actifs');
        $this->line('- Addons: ' . $report['addons']['enabled'] . '/' . $report['addons']['total'] . ' actifs');
        $this->line('- storage writable: ' . ($report['storage_writable'] ? 'yes' : 'no'));
        $this->line('- cache writable: ' . ($report['cache_writable'] ? 'yes' : 'no'));

        if (!empty($report['module_state_issues'])) {
            $this->warn('Issues modules detectees:');
            foreach ($report['module_state_issues'] as $issue) {
                $this->line('  • ' . ($issue['message'] ?? 'issue'));
            }
        }

        if (!empty($report['migration_collisions'])) {
            $this->warn('Collisions migrations detectees:');
            foreach ($report['migration_collisions'] as $name => $paths) {
                $this->line("  • {$name}");
                foreach ($paths as $path) {
                    $this->line("      - {$path}");
                }
            }
        }

        $healthy = empty($report['module_state_issues']) && empty($report['migration_collisions']) && $report['storage_writable'] && $report['cache_writable'];

        if ($healthy) {
            $this->info('Etat systeme: OK');
            return self::SUCCESS;
        }

        $this->error('Etat systeme: NOK');
        return self::FAILURE;
    }
}
