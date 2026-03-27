<?php

namespace App\Console\Commands;

use App\Services\InstallCheckService;
use Illuminate\Console\Command;

class CatminInstallCheckCommand extends Command
{
    protected $signature = 'catmin:install:check {--json : sortie JSON des checks}';

    protected $description = 'Verifier les prerequis d\'installation CATMIN (PHP, extensions, DB, dossiers, env)';

    public function handle(): int
    {
        $report = InstallCheckService::run();

        if ((bool) $this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            return ($report['ok'] ?? false) ? self::SUCCESS : self::FAILURE;
        }

        $this->info('CATMIN install check');

        foreach ($report['checks'] as $name => $check) {
            $status = ($check['ok'] ?? false) ? 'OK' : 'KO';
            $label = str_pad((string) $name, 16, ' ');
            $this->line("- {$label} [{$status}] " . ($check['message'] ?? ''));
        }

        if (($report['ok'] ?? false) === true) {
            $this->info('Environnement compatible avec CATMIN.');
            return self::SUCCESS;
        }

        $this->error('Installation non prete: corrigez les checks KO.');
        return self::FAILURE;
    }
}
