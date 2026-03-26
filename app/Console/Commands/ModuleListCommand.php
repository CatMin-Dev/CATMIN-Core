<?php

namespace App\Console\Commands;

use App\Services\ModuleManager;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('module:list')]
#[Description('List all available modules and their status')]
class ModuleListCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $modules = ModuleManager::all();

        if ($modules->isEmpty()) {
            $this->info('No modules found.');
            return 0;
        }

        $this->newLine();
        $this->info('CATMIN Modules');
        $this->line('═══════════════════════════════════════════════════════════════════');

        $headers = ['Module', 'Slug', 'Version', 'Status', 'Dependencies'];
        $rows = [];

        foreach ($modules as $module) {
            $rows[] = [
                $module->name,
                $module->slug,
                $module->version ?? 'unknown',
                $module->enabled ? '<fg=green>✓ Enabled</>' : '<fg=red>✗ Disabled</>',
                count($module->depends ?? []) > 0 ? implode(', ', $module->depends) : '—',
            ];
        }

        $this->table($headers, $rows);

        // Summary
        $this->newLine();
        $summary = ModuleManager::summary();
        $this->line("Total: <fg=blue>{$summary['total']}</> modules");
        $this->line("<fg=green>Enabled: {$summary['enabled']}</> | <fg=red>Disabled: {$summary['disabled']}</>");
        $this->newLine();

        return 0;
    }
}
