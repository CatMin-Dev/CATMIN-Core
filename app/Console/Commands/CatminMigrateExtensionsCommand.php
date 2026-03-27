<?php

namespace App\Console\Commands;

use App\Services\AddonManager;
use App\Services\AddonMigrationRunner;
use App\Services\MigrationCollisionService;
use App\Services\ModuleManager;
use App\Services\ModuleMigrationRunner;
use Illuminate\Console\Command;

class CatminMigrateExtensionsCommand extends Command
{
    protected $signature = 'catmin:migrate:extensions
        {--modules : Exécute uniquement les migrations des modules actifs}
        {--addons : Exécute uniquement les migrations des addons actifs}
        {--dry-run : Affiche seulement ce qui serait exécuté}';

    protected $description = 'Exécute les migrations par module et/ou addon avec garde anti-collisions de fichiers';

    public function handle(): int
    {
        $runModules = (bool) $this->option('modules');
        $runAddons = (bool) $this->option('addons');
        $dryRun = (bool) $this->option('dry-run');

        if (!$runModules && !$runAddons) {
            $runModules = true;
            $runAddons = true;
        }

        $collisions = MigrationCollisionService::detectBasenameCollisions();
        if (!empty($collisions)) {
            $this->error('Collisions de noms de fichiers migration détectées:');
            foreach ($collisions as $basename => $paths) {
                $this->line('- ' . $basename);
                foreach ($paths as $path) {
                    $this->line('    • ' . $path);
                }
            }

            return self::FAILURE;
        }

        $modulesDone = 0;
        $addonsDone = 0;

        if ($runModules) {
            $this->info('Modules actifs:');
            foreach (ModuleManager::enabled() as $module) {
                $slug = (string) $module->slug;
                $this->line("- {$module->name} ({$slug})");
                if (!$dryRun) {
                    $result = ModuleMigrationRunner::runForModule($slug);
                    $modulesDone += $result['ran'] > 0 ? 1 : 0;
                }
            }
        }

        if ($runAddons) {
            $this->info('Addons actifs:');
            foreach (AddonManager::enabled() as $addon) {
                $slug = (string) $addon->slug;
                $this->line("- {$addon->name} ({$slug})");
                if (!$dryRun) {
                    $result = AddonMigrationRunner::runForAddon($slug);
                    $addonsDone += $result['ran'] > 0 ? 1 : 0;
                }
            }
        }

        if ($dryRun) {
            $this->comment('Dry-run terminé.');
            return self::SUCCESS;
        }

        $this->info("Terminé: {$modulesDone} module(s) et {$addonsDone} addon(s) avec migrations exécutées.");

        return self::SUCCESS;
    }
}
