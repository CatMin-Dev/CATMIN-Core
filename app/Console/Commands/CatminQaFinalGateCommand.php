<?php

namespace App\Console\Commands;

use App\Services\QaFinalGateService;
use Illuminate\Console\Command;

class CatminQaFinalGateCommand extends Command
{
    protected $signature = 'catmin:qa:final-gate
        {--json : Affiche le rapport JSON dans la console}
        {--save : Sauvegarde le rapport dans storage/app/reports}
        {--with-tests : Lance aussi la suite de tests auto V2+}
        {--strict-manual : Les checks manuels critiques deviennent bloquants}';

    protected $description = 'QA Final Gate V2: checklist complete, validations auto/manuelles, statut READY/NOT READY';

    public function handle(): int
    {
        $report = QaFinalGateService::run(
            withAutomatedTests: (bool) $this->option('with-tests'),
            strictManual: (bool) $this->option('strict-manual')
        );

        if ((bool) $this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            return (string) ($report['status'] ?? 'NOT READY') === 'READY' ? self::SUCCESS : self::FAILURE;
        }

        $summary = (array) ($report['summary'] ?? []);

        $this->info('CATMIN QA Final Gate');
        $this->line('Status: ' . (string) ($report['status'] ?? 'NOT READY'));
        $this->line('Automated checks: ' . (int) ($summary['automated_ok'] ?? 0) . '/' . (int) ($summary['automated_total'] ?? 0) . ' OK');
        $this->line('Manual checks: ' . (int) ($summary['manual_passed'] ?? 0) . '/' . (int) ($summary['manual_total'] ?? 0) . ' PASS');
        $this->line('Blockers: ' . (int) ($summary['blockers'] ?? 0));

        $this->line('');
        $this->line('Release criteria:');
        foreach ((array) data_get($report, 'sections.release_criteria', []) as $criterion) {
            $status = (bool) ($criterion['ok'] ?? false) ? 'OK' : 'NOK';
            $this->line('  [' . $status . '] ' . (string) ($criterion['label'] ?? 'criterion'));
        }

        $blockers = (array) ($report['blockers'] ?? []);
        if ($blockers !== []) {
            $this->line('');
            $this->warn('Blockers:');
            foreach ($blockers as $blocker) {
                $this->line('  - ' . (string) $blocker);
            }
        }

        if ((bool) $this->option('save')) {
            $paths = QaFinalGateService::writeReport($report);
            $this->line('');
            $this->info('Reports generated:');
            $this->line('- JSON: ' . (string) ($paths['json'] ?? '')); 
            $this->line('- Markdown: ' . (string) ($paths['markdown'] ?? ''));
        }

        return (string) ($report['status'] ?? 'NOT READY') === 'READY' ? self::SUCCESS : self::FAILURE;
    }
}
