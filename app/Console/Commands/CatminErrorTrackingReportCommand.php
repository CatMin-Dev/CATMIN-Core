<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Logger\Services\ErrorTrackingService;

class CatminErrorTrackingReportCommand extends Command
{
    protected $signature = 'catmin:error:report {--hours=24 : Fenetre d\'analyse en heures}';

    protected $description = 'Affiche un rapport de suivi des erreurs applicatives';

    public function handle(): int
    {
        $hours = max(1, (int) $this->option('hours'));
        $summary = ErrorTrackingService::summary($hours);

        $this->line('<fg=blue>═══════════════════════════════════</>');
        $this->line('<fg=blue>CATMIN Error Tracking</>');
        $this->line('<fg=blue>═══════════════════════════════════</>');
        $this->line('Window: ' . $summary['window_hours'] . 'h');
        $this->line('Errors: ' . $summary['errors']);

        $rows = collect((array) ($summary['top'] ?? []))
            ->map(fn (array $entry) => [
                (string) ($entry['fingerprint'] ?? ''),
                (string) ($entry['count'] ?? 0),
                (string) ($entry['exception'] ?? ''),
                (string) \Illuminate\Support\Str::limit((string) ($entry['message'] ?? ''), 70),
                (string) ($entry['last_seen'] ?? ''),
            ])
            ->values()
            ->toArray();

        if ($rows !== []) {
            $this->table(['Fingerprint', 'Count', 'Exception', 'Message', 'Last Seen'], $rows);
        }

        return self::SUCCESS;
    }
}
