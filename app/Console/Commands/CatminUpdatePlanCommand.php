<?php

namespace App\Console\Commands;

use App\Services\UpdatePlannerService;
use Illuminate\Console\Command;

class CatminUpdatePlanCommand extends Command
{
    protected $signature = 'catmin:update:plan {--json : Afficher le plan en JSON}';

    protected $description = 'Affiche le plan de mise à jour CATMIN (core/modules/addons)';

    public function handle(): int
    {
        $plan = UpdatePlannerService::plan();

        if ((bool) $this->option('json')) {
            $this->line(json_encode($plan, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            return self::SUCCESS;
        }

        $this->info('Stratégie CATMIN update');
        foreach ($plan['strategy'] as $key => $value) {
            $this->line("- {$key}: {$value}");
        }

        $this->newLine();
        $this->info('Workflow recommandé');
        foreach ($plan['workflow'] as $step) {
            $this->line($step);
        }

        $this->newLine();
        $this->info('Upgrades détectés');

        $modules = $plan['pending_upgrades']['modules'];
        if (empty($modules)) {
            $this->line('- Modules: aucun upgrade détecté');
        } else {
            $this->line('- Modules:');
            foreach ($modules as $entry) {
                $this->line("  • {$entry['name']} ({$entry['slug']}): {$entry['installed']} -> {$entry['target']}");
            }
        }

        $addons = $plan['pending_upgrades']['addons'];
        if (empty($addons)) {
            $this->line('- Addons: aucun upgrade détecté');
        } else {
            $this->line('- Addons:');
            foreach ($addons as $entry) {
                $this->line("  • {$entry['name']} ({$entry['slug']}): {$entry['installed']} -> {$entry['target']}");
            }
        }

        $collisions = $plan['migration_collisions'];
        if (!empty($collisions)) {
            $this->newLine();
            $this->error('Collisions de migrations détectées:');
            foreach ($collisions as $basename => $paths) {
                $this->line("- {$basename}");
                foreach ($paths as $path) {
                    $this->line("    • {$path}");
                }
            }

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
