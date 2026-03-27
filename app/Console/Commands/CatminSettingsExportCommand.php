<?php

namespace App\Console\Commands;

use App\Services\SettingsTransferService;
use Illuminate\Console\Command;

class CatminSettingsExportCommand extends Command
{
    protected $signature = 'catmin:settings:export
        {path? : Fichier JSON de sortie}
        {--include-defaults : Inclut aussi les valeurs par defaut du config catmin.settings.defaults}';

    protected $description = 'Exporte les settings CATMIN au format JSON lisible';

    public function handle(): int
    {
        $path = (string) ($this->argument('path') ?: storage_path('app/settings/catmin-settings-' . now()->format('Ymd-His') . '.json'));
        $includeDefaults = (bool) $this->option('include-defaults');

        SettingsTransferService::exportToFile($path, $includeDefaults);

        $this->info('Export settings termine.');
        $this->line('Fichier: ' . $path);
        $this->line('Include defaults: ' . ($includeDefaults ? 'yes' : 'no'));

        return self::SUCCESS;
    }
}
