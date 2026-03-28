<?php

namespace App\Console\Commands;

use App\Services\CatminEventBus;
use App\Services\AddonManager;
use Illuminate\Console\Command;

class CatminAddonEnableCommand extends Command
{
    protected $signature = 'catmin:addon:enable {slug : Addon slug}';

    protected $description = 'Activer un addon CATMIN';

    public function handle(): int
    {
        $slug = (string) $this->argument('slug');

        if (!AddonManager::exists($slug)) {
            $this->error("Addon introuvable: {$slug}");
            return self::FAILURE;
        }

        if (!AddonManager::enable($slug)) {
            $this->error("Impossible d'activer l'addon: {$slug}");
            return self::FAILURE;
        }

        CatminEventBus::dispatch(CatminEventBus::ADDON_ENABLED, [
            'slug' => $slug,
            'enabled' => true,
        ]);

        $this->info("Addon '{$slug}' active.");

        return self::SUCCESS;
    }
}
