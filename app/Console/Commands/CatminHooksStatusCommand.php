<?php

namespace App\Console\Commands;

use App\Services\CatminHookRegistry;
use Illuminate\Console\Command;

class CatminHooksStatusCommand extends Command
{
    protected $signature = 'catmin:hooks:status {--only-active : Afficher uniquement les slots ayant au moins un callback}';

    protected $description = 'Afficher les slots hooks CATMIN et le nombre de callbacks enregistres';

    public function handle(): int
    {
        $onlyActive = (bool) $this->option('only-active');

        $rows = collect(CatminHookRegistry::registry())
            ->filter(fn (array $entry) => !$onlyActive || $entry['callbacks'] > 0)
            ->map(fn (array $entry) => [$entry['name'], (string) $entry['callbacks']])
            ->values()
            ->toArray();

        if ($rows === []) {
            $this->info('Aucun hook a afficher.');
            return self::SUCCESS;
        }

        $this->table(['Hook Slot', 'Callbacks'], $rows);

        return self::SUCCESS;
    }
}
