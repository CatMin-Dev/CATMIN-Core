<?php

namespace App\Console\Commands;

use App\Services\InstallCheckService;
use Illuminate\Console\Command;

class CatminInstallCheckCommand extends Command
{
    protected $signature = 'catmin:install:check {--json : sortie JSON des checks}';

    protected $description = 'Verifier les prerequis d\'installation CATMIN (PHP, extensions, DB, dossiers, env, guardrails securite)';

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
            $severity = strtoupper((string) ($check['status'] ?? 'ok'));
            $label = str_pad((string) $name, 20, ' ');
            $this->line("- {$label} [{$status}/{$severity}] " . ($check['message'] ?? ''));
        }

        $warningCount = count((array) ($report['warnings'] ?? []));
        if ($warningCount > 0) {
            $this->warn("{$warningCount} warning(s) detecte(s): verifier les guardrails de securite.");
        }

        if (($report['ok'] ?? false) === true) {
            $this->info('Environnement compatible avec CATMIN.');
            return self::SUCCESS;
        }

        $this->error('Installation non prete: corrigez les checks KO.');
        return self::FAILURE;
    }
}
