<?php

namespace App\Console\Commands;

use App\Services\ExtensionContractValidatorService;
use Illuminate\Console\Command;

class CatminValidateAddonCommand extends Command
{
    protected $signature = 'catmin:validate-addon
        {slug : Addon slug}
        {--json : sortie JSON complete}';

    protected $description = 'Valide le contrat d architecture d un addon CATMIN';

    public function __construct(private readonly ExtensionContractValidatorService $validator)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $slug = trim((string) $this->argument('slug'));
        $report = $this->validator->validateAddon($slug);

        if ((bool) $this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            return (bool) ($report['ok'] ?? false) ? self::SUCCESS : self::FAILURE;
        }

        $this->info('CATMIN Addon Contract Validation');
        $this->line('- Addon: ' . (string) ($report['slug'] ?? $slug));
        $this->line('- Path: ' . (string) ($report['path'] ?? 'n/a'));
        $this->line('- Result: ' . ((bool) ($report['ok'] ?? false) ? 'OK' : 'NOK'));
        $this->line('- Summary: ' . (string) ($report['summary'] ?? 'n/a'));

        $errors = (array) ($report['errors'] ?? []);
        $warnings = (array) ($report['warnings'] ?? []);

        if ($errors !== []) {
            $this->line('');
            $this->error('Erreurs:');
            foreach ($errors as $row) {
                $this->line('- ' . (string) $row);
            }
        }

        if ($warnings !== []) {
            $this->line('');
            $this->warn('Warnings:');
            foreach ($warnings as $row) {
                $this->line('- ' . (string) $row);
            }
        }

        return (bool) ($report['ok'] ?? false) ? self::SUCCESS : self::FAILURE;
    }
}
