<?php

namespace App\Console\Commands;

use App\Services\AddonManager;
use App\Services\AddonMigrationRunner;
use App\Services\CatminEventBus;
use Illuminate\Console\Command;

class CatminAddonInstallCommand extends Command
{
    protected $signature = 'catmin:addon:install
        {slug : Addon slug}
        {--no-enable : N active pas automatiquement}
        {--no-migrate : N execute pas les migrations}';

    protected $description = 'Installer un addon present dans addons/ (validation + activation + migrations)';

    public function handle(): int
    {
        $slug = (string) $this->argument('slug');

        $addon = AddonManager::find($slug);
        if ($addon === null) {
            $this->error("Addon introuvable: {$slug}");
            return self::FAILURE;
        }

        $missing = AddonManager::missingStructure($addon);
        if (!empty($missing)) {
            $this->error('Structure addon invalide, elements manquants: ' . implode(', ', $missing));
            return self::FAILURE;
        }

        $validation = AddonManager::canEnable($slug);
        if (!$validation['allowed']) {
            $this->error($validation['message']);
            return self::FAILURE;
        }

        if (!(bool) $this->option('no-enable')) {
            if (!AddonManager::enable($slug)) {
                $this->error('Impossible d\'activer l\'addon.');
                return self::FAILURE;
            }
            $this->info("Addon '{$slug}' active.");
        }

        if (!(bool) $this->option('no-migrate')) {
            $result = AddonMigrationRunner::runForAddon($slug);
            $this->line("Migrations executees: {$result['ran']}");
        }

        CatminEventBus::dispatch(CatminEventBus::ADDON_INSTALLED, [
            'slug' => $slug,
            'enabled' => !(bool) $this->option('no-enable'),
            'migrations_ran' => !(bool) $this->option('no-migrate'),
        ]);

        $this->info("Addon '{$slug}' installe.");

        return self::SUCCESS;
    }
}
