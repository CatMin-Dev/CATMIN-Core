<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class CatminModuleTestCommand extends Command
{
    protected $signature = 'catmin:test:module
        {slug? : Slug module (ex: queue)}
        {--suite=all : all|unit|feature}';

    protected $description = 'Execute le framework de tests modules CATMIN';

    public function handle(): int
    {
        $slug = trim((string) ($this->argument('slug') ?? ''));
        $suite = strtolower((string) $this->option('suite'));

        $paths = match ($suite) {
            'unit' => ['tests/Unit/Modules'],
            'feature' => ['tests/Feature/Modules'],
            default => ['tests/Unit/Modules', 'tests/Feature/Modules'],
        };

        $command = array_merge(['php', 'artisan', 'test'], $paths);

        if ($slug !== '') {
            $filter = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $slug))) . 'Module';
            $command[] = '--filter=' . $filter;
        }

        $this->line('Running: ' . implode(' ', $command));

        $process = new Process($command, base_path());
        $process->setTimeout(0);
        $process->run(function (string $type, string $buffer): void {
            $this->output->write($buffer);
        });

        return $process->isSuccessful() ? self::SUCCESS : self::FAILURE;
    }
}
