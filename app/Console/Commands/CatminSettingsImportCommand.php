<?php

namespace App\Console\Commands;

use App\Services\SettingsTransferService;
use Illuminate\Console\Command;
use InvalidArgumentException;

class CatminSettingsImportCommand extends Command
{
    protected $signature = 'catmin:settings:import
        {path : Fichier JSON a importer}
        {--overwrite : Ecrase les cles deja existantes}
        {--dry-run : Simule sans ecrire en base}
        {--allow-protected : Autorise les cles protegees (usage avance uniquement)}';

    protected $description = 'Importe des settings CATMIN avec validation minimale et garde-fous';

    public function handle(): int
    {
        $path = (string) $this->argument('path');
        $overwrite = (bool) $this->option('overwrite');
        $dryRun = (bool) $this->option('dry-run');
        $allowProtected = (bool) $this->option('allow-protected');

        try {
            $result = SettingsTransferService::importFromFile(
                $path,
                overwrite: $overwrite,
                dryRun: $dryRun,
                allowProtected: $allowProtected
            );
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        foreach ($result['warnings'] as $warning) {
            $this->warn($warning);
        }

        foreach ($result['errors'] as $error) {
            $this->error($error);
        }

        $this->info($dryRun ? 'Import simulation terminee.' : 'Import termine.');
        $this->table(['created', 'updated', 'skipped', 'errors'], [[
            $result['created'],
            $result['updated'],
            $result['skipped'],
            count($result['errors']),
        ]]);

        return $result['errors'] === [] ? self::SUCCESS : self::FAILURE;
    }
}
