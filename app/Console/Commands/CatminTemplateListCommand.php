<?php

namespace App\Console\Commands;

use App\Services\TemplateInstallerService;
use Illuminate\Console\Command;

class CatminTemplateListCommand extends Command
{
    protected $signature = 'catmin:template:list {--json : sortie JSON complete}';

    protected $description = 'Lister les templates installables CATMIN';

    public function __construct(private readonly TemplateInstallerService $templateInstallerService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $result = $this->templateInstallerService->listTemplates();
        $templates = (array) ($result['templates'] ?? []);

        if ((bool) $this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return self::SUCCESS;
        }

        $rows = array_map(static fn (array $t) => [
            (string) ($t['name'] ?? ''),
            (string) ($t['slug'] ?? ''),
            (string) ($t['version'] ?? '1.0.0'),
            (bool) ($t['valid'] ?? false) ? 'yes' : 'no',
            implode(', ', array_filter([
                'p:' . (int) (($t['sections']['pages'] ?? 0)),
                'a:' . (int) (($t['sections']['articles'] ?? 0)),
                'm:' . (int) (($t['sections']['menus'] ?? 0)),
                'b:' . (int) (($t['sections']['blocks'] ?? 0)),
                's:' . (int) (($t['sections']['settings'] ?? 0)),
                'mp:' . (int) (($t['sections']['media_placeholders'] ?? 0)),
            ])),
        ], $templates);

        $this->table(['Name', 'Slug', 'Version', 'Valid', 'Sections'], $rows);

        $invalid = array_filter($templates, static fn (array $t) => !(bool) ($t['valid'] ?? false));
        if ($invalid !== []) {
            $this->warn(count($invalid) . ' template(s) invalide(s). Utiliser --json pour le detail des erreurs.');
        }

        return self::SUCCESS;
    }
}
