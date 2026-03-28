<?php

namespace App\Console\Commands;

use App\Services\CatminEventMapService;
use Illuminate\Console\Command;

class CatminEventMapStatusCommand extends Command
{
    protected $signature = 'catmin:event-map:status {--json : sortie JSON}';

    protected $description = 'Etat de la cartographie d\'events CATMIN (documentes, implementes, cables)';

    public function handle(): int
    {
        $status = CatminEventMapService::status();

        if ((bool) $this->option('json')) {
            $this->line(json_encode($status, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            return self::SUCCESS;
        }

        $counts = (array) ($status['counts'] ?? []);
        $this->info('CATMIN Event Map Status');
        $this->line('- Documentes: ' . (int) ($counts['documented'] ?? 0));
        $this->line('- Implementes: ' . (int) ($counts['implemented'] ?? 0));
        $this->line('- Cables (dispatch reel): ' . (int) ($counts['wired'] ?? 0));
        $this->line('- Documentes non implementes: ' . (int) ($counts['documented_only'] ?? 0));

        $implementedRows = collect((array) ($status['implemented'] ?? []))
            ->map(fn (string $name) => [$name])
            ->values()
            ->toArray();

        $documentedOnlyRows = collect((array) ($status['documented_only'] ?? []))
            ->take(30)
            ->map(fn (string $name) => [$name])
            ->values()
            ->toArray();

        $listenerRows = collect((array) ($status['base_listeners'] ?? []))
            ->map(fn (array $row) => [(string) $row['name'], (string) $row['listeners']])
            ->values()
            ->toArray();

        if ($implementedRows !== []) {
            $this->line('');
            $this->table(['Events implementes'], $implementedRows);
        }

        if ($documentedOnlyRows !== []) {
            $this->line('');
            $this->warn('Events documentes mais non encore implementes (top 30):');
            $this->table(['Events'], $documentedOnlyRows);
        }

        if ($listenerRows !== []) {
            $this->line('');
            $this->info('Listeners de base actifs:');
            $this->table(['Event', 'Listeners'], $listenerRows);
        }

        return self::SUCCESS;
    }
}
