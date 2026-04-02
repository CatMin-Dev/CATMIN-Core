<?php

namespace App\Console\Commands;

use App\Services\TemplateInstallerService;
use Illuminate\Console\Command;

class CatminTemplateInstallCommand extends Command
{
    protected $signature = 'catmin:template:install
        {slug : Template slug}
        {--force : Autorise overwrite des donnees existantes}
        {--json : sortie JSON complete}';

    protected $description = 'Installer un template CATMIN';

    public function __construct(private readonly TemplateInstallerService $templateInstallerService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $slug = strtolower(trim((string) $this->argument('slug')));

        $result = $this->templateInstallerService->installFromSlug($slug, [
            'overwrite' => (bool) $this->option('force'),
            'source' => 'cli',
        ]);

        if ((bool) $this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return ($result['ok'] ?? false) ? self::SUCCESS : self::FAILURE;
        }

        if (($result['ok'] ?? false) !== true) {
            $this->error((string) ($result['message'] ?? 'Installation template echouee.'));
            foreach ((array) ($result['errors'] ?? []) as $error) {
                $this->line('- ' . $error);
            }
            return self::FAILURE;
        }

        $summary = (array) ($result['summary'] ?? []);
        $this->info('Template installe: ' . (string) (($result['template']['slug'] ?? $slug)));
        $this->line('Pages: ' . (int) ($summary['pages'] ?? 0));
        $this->line('Articles: ' . (int) ($summary['articles'] ?? 0));
        $this->line('Menus: ' . (int) ($summary['menus'] ?? 0));
        $this->line('Menu items: ' . (int) ($summary['menu_items'] ?? 0));
        $this->line('Blocks: ' . (int) ($summary['blocks'] ?? 0));
        $this->line('Settings: ' . (int) ($summary['settings'] ?? 0));
        $this->line('Media placeholders: ' . (int) ($summary['media_placeholders'] ?? 0));

        return self::SUCCESS;
    }
}
