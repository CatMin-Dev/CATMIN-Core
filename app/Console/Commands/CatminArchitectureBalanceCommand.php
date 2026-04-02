<?php

namespace App\Console\Commands;

use App\Services\ExtensionContractValidatorService;
use Illuminate\Console\Command;

class CatminArchitectureBalanceCommand extends Command
{
    protected $signature = 'catmin:architecture:balance
        {--json : sortie JSON complete}';

    protected $description = 'Verifie l ordre d architecture CORE -> MODULES -> ADDONS et les dependances associees';

    public function __construct(private readonly ExtensionContractValidatorService $validator)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $report = $this->validator->validateArchitectureBalance();

        if ((bool) $this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            return (bool) ($report['ok'] ?? false) ? self::SUCCESS : self::FAILURE;
        }

        $this->info('CATMIN Architecture Balance Check');
        $this->line('- Rule: CORE -> MODULES -> ADDONS');
        $this->line('- Result: ' . ((bool) ($report['ok'] ?? false) ? 'OK' : 'NOK'));
        $this->line('- Summary: ' . (string) ($report['summary'] ?? 'n/a'));

        $checks = (array) ($report['checks'] ?? []);
        if ($checks !== []) {
            $this->line('');
            $this->line('Checks:');
            foreach ($checks as $label => $ok) {
                $this->line(sprintf('- [%s] %s', (bool) $ok ? 'OK' : 'NOK', (string) $label));
            }
        }

        $errors = (array) ($report['errors'] ?? []);
        if ($errors !== []) {
            $this->line('');
            $this->error('Violations:');
            foreach ($errors as $error) {
                $this->line('- ' . (string) $error);
            }
        }

        return (bool) ($report['ok'] ?? false) ? self::SUCCESS : self::FAILURE;
    }
}
