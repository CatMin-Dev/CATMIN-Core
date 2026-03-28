<?php

namespace App\Console\Commands;

use App\Services\AddonMarketplaceService;
use Illuminate\Console\Command;

class CatminAddonMarketplaceIndexCommand extends Command
{
    protected $signature = 'catmin:addon:marketplace:index';

    protected $description = 'Reconstruit l\'index local du marketplace addons (packages zip + versions)';

    public function handle(): int
    {
        $index = AddonMarketplaceService::buildIndex();

        $this->info('Index marketplace addons reconstruit.');
        $this->line('Packages: ' . (string) ($index['packages_count'] ?? 0));
        $this->line('Fichier: ' . AddonMarketplaceService::indexPath());

        return self::SUCCESS;
    }
}
