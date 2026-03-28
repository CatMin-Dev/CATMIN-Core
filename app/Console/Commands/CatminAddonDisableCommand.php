<?php

namespace App\Console\Commands;

use App\Services\AddonManager;
use Illuminate\Console\Command;

class CatminAddonDisableCommand extends Command
{
    protected $signature = 'catmin:addon:disable {slug : Addon slug}';

    protected $description = 'Desactiver un addon CATMIN';

    public function handle(): int
    {
        $slug = (string) $this->argument('slug');

        if (!AddonManager::exists($slug)) {
            $this->error("Addon introuvable: {$slug}");
            return self::FAILURE;
        }

        if (!AddonManager::disable($slug)) {
            $this->error("Impossible de desactiver l'addon: {$slug}");
            return self::FAILURE;
        }

        $this->info("Addon '{$slug}' desactive.");

        return self::SUCCESS;
    }
}
