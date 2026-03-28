<?php

namespace App\Console\Commands;

use App\Services\AddonManager;
use App\Services\AddonMigrationRunner;
use App\Services\CatminEventBus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CatminAddonUninstallCommand extends Command
{
    protected $signature = 'catmin:addon:uninstall
        {slug : Addon slug}
        {--rollback : Rollback migrations addon avant desinstallation}
        {--step=0 : Nombre d\'etapes de rollback (0 = rollback complet du path addon)}
        {--delete-files : Supprimer physiquement le dossier addon}';

    protected $description = 'Desinstaller un addon CATMIN (disable + rollback optionnel + suppression optionnelle)';

    public function handle(): int
    {
        $slug = (string) $this->argument('slug');
        $addon = AddonManager::find($slug);

        if ($addon === null) {
            $this->error("Addon introuvable: {$slug}");
            return self::FAILURE;
        }

        if ((bool) ($addon->enabled ?? false) && !AddonManager::disable($slug)) {
            $this->error("Impossible de desactiver l'addon: {$slug}");
            return self::FAILURE;
        }

        CatminEventBus::dispatch(CatminEventBus::ADDON_DISABLED, [
            'slug' => $slug,
            'enabled' => false,
            'source' => 'uninstall',
        ]);

        if ((bool) $this->option('rollback')) {
            $steps = max(0, (int) $this->option('step'));
            $rollback = AddonMigrationRunner::rollbackForAddon($slug, $steps);

            if (!$rollback['rolled_back']) {
                $this->warn("Rollback non applique: {$rollback['output']}");
            } else {
                $this->line('Rollback migrations addon execute.');
            }
        }

        if ((bool) $this->option('delete-files')) {
            $addonPath = (string) ($addon->path ?? '');
            if ($addonPath !== '' && File::isDirectory($addonPath)) {
                File::deleteDirectory($addonPath);
                $this->line("Dossier supprime: {$addonPath}");
            }
        }

        AddonManager::clearCache();

        CatminEventBus::dispatch(CatminEventBus::ADDON_UNINSTALLED, [
            'slug' => $slug,
            'rollback' => (bool) $this->option('rollback'),
            'delete_files' => (bool) $this->option('delete-files'),
        ]);

        $this->info("Addon '{$slug}' desinstalle.");

        return self::SUCCESS;
    }
}
