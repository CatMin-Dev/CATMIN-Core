<?php

namespace App\Console\Commands;

use App\Services\CatminEventBus;
use Illuminate\Console\Command;

class CatminEventBusStatusCommand extends Command
{
    protected $signature = 'catmin:event-bus:status {--only-active : Afficher uniquement les evenements ayant au moins un listener}';

    protected $description = 'Afficher les evenements CATMIN et le nombre de listeners enregistres';

    public function handle(): int
    {
        $onlyActive = (bool) $this->option('only-active');

        $rows = collect(CatminEventBus::registry())
            ->filter(fn (array $entry) => !$onlyActive || $entry['listeners'] > 0)
            ->map(fn (array $entry) => [$entry['name'], (string) $entry['listeners']])
            ->values()
            ->toArray();

        if ($rows === []) {
            $this->info('Aucun evenement a afficher.');
            return self::SUCCESS;
        }

        $this->table(['Event', 'Listeners'], $rows);

        return self::SUCCESS;
    }
}
