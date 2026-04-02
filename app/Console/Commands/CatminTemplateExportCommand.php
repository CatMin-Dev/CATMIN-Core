<?php

namespace App\Console\Commands;

use App\Services\TemplateInstallerService;
use Illuminate\Console\Command;

class CatminTemplateExportCommand extends Command
{
    protected $signature = 'catmin:template:export
        {slug : Slug du template exporte}
        {path? : Fichier template de sortie}
        {--name= : Nom lisible du template}
        {--description= : Description du template}
        {--json : sortie JSON complete}';

    protected $description = 'Exporter les donnees CATMIN au format template installable';

    public function __construct(private readonly TemplateInstallerService $templateInstallerService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $slug = strtolower(trim((string) $this->argument('slug')));
        $path = (string) ($this->argument('path') ?: base_path('templates/' . $slug . '.template.json'));

        $result = $this->templateInstallerService->exportToFile($slug, $path, [
            'name' => (string) ($this->option('name') ?? ''),
            'description' => (string) ($this->option('description') ?? ''),
        ]);

        if ((bool) $this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return ($result['ok'] ?? false) ? self::SUCCESS : self::FAILURE;
        }

        if (($result['ok'] ?? false) !== true) {
            $this->error((string) ($result['message'] ?? 'Export template echoue.'));
            foreach ((array) ($result['errors'] ?? []) as $error) {
                $this->line('- ' . $error);
            }
            return self::FAILURE;
        }

        $this->info('Template exporte.');
        $this->line('Fichier: ' . (string) ($result['path'] ?? $path));

        return self::SUCCESS;
    }
}
