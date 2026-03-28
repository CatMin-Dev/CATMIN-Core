<?php

namespace App\Console\Commands;

use App\Services\CatminEventBus;
use App\Services\ValidationV2PlusService;
use Illuminate\Console\Command;

class CatminValidateV2PlusCommand extends Command
{
    protected $signature = 'catmin:validate:v2-plus
        {--json : sortie JSON}
        {--deep : execute une validation profonde (tests complets)}
        {--skip-tests : ignore les tests de stabilite}';

    protected $description = 'Validation V2+ globale (architecture, tests, stabilite)';

    public function handle(): int
    {
        $deep = (bool) $this->option('deep');
        $skipTests = (bool) $this->option('skip-tests');

        $report = ValidationV2PlusService::run(!$skipTests, $deep);

        CatminEventBus::dispatch(CatminEventBus::SYSTEM_HEALTH_CHECKED, [
            'source' => 'catmin:validate:v2-plus',
            'summary' => (array) ($report['summary'] ?? []),
            'ok' => (bool) ($report['ok'] ?? false),
        ]);

        if ((bool) $this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            return (bool) ($report['ok'] ?? false) ? self::SUCCESS : self::FAILURE;
        }

        $this->info('CATMIN Validation V2+');
        $this->line('- Checks: ' . ($report['summary']['ok'] ?? 0) . '/' . ($report['summary']['total'] ?? 0) . ' OK');

        foreach ((array) ($report['checks'] ?? []) as $check) {
            $status = (bool) ($check['ok'] ?? false) ? 'OK' : 'NOK';
            $this->line(sprintf('  [%s] %s - %s', $status, (string) ($check['label'] ?? 'check'), (string) ($check['details'] ?? '')));
        }

        $tests = (array) ($report['tests'] ?? []);
        if ((bool) ($tests['executed'] ?? false)) {
            $this->line('');
            $this->line(sprintf(
                'Stabilite tests: suite=%s, exit=%d, duree=%dms',
                (string) ($tests['suite'] ?? 'n/a'),
                (int) ($tests['exit_code'] ?? 1),
                (int) ($tests['duration_ms'] ?? 0)
            ));

            if (!(bool) ($tests['ok'] ?? true)) {
                $this->warn('Extrait sortie tests:');
                foreach ((array) ($tests['output'] ?? []) as $line) {
                    $this->line('  ' . (string) $line);
                }
            }
        }

        if ((bool) ($report['ok'] ?? false)) {
            $this->info('Validation V2+: OK');
            return self::SUCCESS;
        }

        $this->error('Validation V2+: NOK');
        return self::FAILURE;
    }
}
