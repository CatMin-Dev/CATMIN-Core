<?php

namespace App\Console\Commands;

use App\Services\V2FreezeReadinessService;
use Illuminate\Console\Command;

class CatminFreezeV2Command extends Command
{
    protected $signature = 'catmin:freeze:v2
        {--json : sortie JSON complete}
        {--with-tests : execute aussi les tests automatises via V2+ et QA gate}';

    protected $description = 'Valide la readiness de freeze V2 stable et prepare le handover V3';

    public function handle(): int
    {
        $report = V2FreezeReadinessService::run((bool) $this->option('with-tests'));

        if ((bool) $this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            return (string) ($report['status'] ?? 'NOT_READY') === 'READY_TO_FREEZE' ? self::SUCCESS : self::FAILURE;
        }

        $summary = (array) ($report['summary'] ?? []);

        $this->info('CATMIN V2 Freeze Readiness');
        $this->line('Status: ' . (string) ($report['status'] ?? 'NOT_READY'));
        $this->line('Checks: ' . (int) ($summary['checks_ok'] ?? 0) . '/' . (int) ($summary['checks_total'] ?? 0) . ' OK');
        $this->line('Critical blockers: ' . (int) ($summary['critical_blockers'] ?? 0));

        $this->line('');
        $this->line('Scope admission rule:');
        $rule = (array) data_get($report, 'scope.admission_rule', []);
        foreach ($rule as $key => $text) {
            $this->line('- ' . (string) $key . ': ' . (string) $text);
        }

        $checks = (array) ($report['checks'] ?? []);
        if ($checks !== []) {
            $this->line('');
            $this->line('Checks detail:');
            foreach ($checks as $check) {
                $ok = (bool) ($check['ok'] ?? false);
                $status = $ok ? 'OK' : 'NOK';
                $this->line(sprintf(
                    '  [%s] %s - %s',
                    $status,
                    (string) ($check['label'] ?? 'check'),
                    (string) ($check['details'] ?? '')
                ));
            }
        }

        $blockers = (array) ($report['blockers'] ?? []);
        if ($blockers !== []) {
            $this->line('');
            $this->error('Freeze blockers:');
            foreach ($blockers as $blocker) {
                $this->line('- ' . (string) $blocker);
            }
        }

        return (string) ($report['status'] ?? 'NOT_READY') === 'READY_TO_FREEZE' ? self::SUCCESS : self::FAILURE;
    }
}
