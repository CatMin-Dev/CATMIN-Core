<?php

namespace App\Console\Commands;

use App\Services\AddonManager;
use App\Services\CatminEventBus;
use App\Services\HealthCheckService;
use App\Services\MigrationCollisionService;
use App\Services\ModuleManager;
use Illuminate\Console\Command;

class CatminSystemCheckCommand extends Command
{
    protected $signature = 'catmin:system:check {--json : sortie JSON}';

    protected $description = 'Verifier l\'etat global CATMIN (modules, addons, collisions migrations, dossier storage)';

    public function handle(): int
    {
        $health = HealthCheckService::run();

        $report = [
            'health' => $health,
            'modules' => ModuleManager::summary(),
            'addons' => AddonManager::summary(),
            'module_state_issues' => ModuleManager::stateIssues(),
            'migration_collisions' => MigrationCollisionService::detectBasenameCollisions(),
        ];

        CatminEventBus::dispatch(CatminEventBus::SYSTEM_HEALTH_CHECKED, [
            'source' => 'catmin:system:check',
            'health' => [
                'ok' => (bool) ($health['ok'] ?? false),
                'summary' => (array) ($health['summary'] ?? []),
            ],
        ]);

        if ((bool) $this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $healthy = $health['ok']
                && empty($report['module_state_issues'])
                && empty($report['migration_collisions']);

            return $healthy ? self::SUCCESS : self::FAILURE;
        }

        $this->info('CATMIN system check');
        $this->line('- Modules: ' . $report['modules']['enabled'] . '/' . $report['modules']['total'] . ' actifs');
        $this->line('- Addons: ' . $report['addons']['enabled'] . '/' . $report['addons']['total'] . ' actifs');

        $this->line('- Health checks: ' . $health['summary']['ok'] . '/' . $health['summary']['total'] . ' OK');
        foreach ($health['checks'] as $check) {
            $status = $check['ok'] ? 'OK' : 'NOK';
            $this->line("  [{$status}] {$check['label']} - {$check['details']}");
        }

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

        $healthy = $health['ok']
            && empty($report['module_state_issues'])
            && empty($report['migration_collisions']);

        if ($healthy) {
            $this->info('Etat systeme: OK');
            return self::SUCCESS;
        }

        $this->error('Etat systeme: NOK');
        return self::FAILURE;
    }
}
