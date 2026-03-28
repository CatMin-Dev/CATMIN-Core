<?php

namespace App\Console\Commands;

use App\Services\AddonManager;
use App\Services\AddonMigrationRunner;
use Illuminate\Console\Command;

class CatminAddonMigrationsStatusCommand extends Command
{
    protected $signature = 'catmin:addon:migrations:status
        {slug? : Addon slug}
        {--only-pending : Afficher uniquement les addons avec migrations ou upgrade en attente}';

    protected $description = 'Afficher le statut des migrations et versions des addons CATMIN';

    public function handle(): int
    {
        $slug = $this->argument('slug');
        $onlyPending = (bool) $this->option('only-pending');

        if ($slug !== null) {
            return $this->renderSingle((string) $slug);
        }

        $rows = AddonManager::all()
            ->map(function ($addon) {
                return AddonMigrationRunner::statusForAddon((string) $addon->slug);
            })
            ->filter(function (array $status) use ($onlyPending) {
                if (!$onlyPending) {
                    return true;
                }

                return $status['has_pending'] || $status['has_version_upgrade'];
            })
            ->map(fn (array $status) => [
                $status['name'],
                $status['slug'],
                $status['declared_version'] !== '' ? $status['declared_version'] : 'n/a',
                $status['installed_version'] !== '' ? $status['installed_version'] : 'n/a',
                $status['has_migrations'] ? 'yes' : 'no',
                $status['has_pending'] ? 'yes' : 'no',
                $status['has_version_upgrade'] ? 'yes' : 'no',
            ])
            ->values()
            ->toArray();

        if ($rows === []) {
            $this->info('Aucun addon a afficher.');
            return self::SUCCESS;
        }

        $this->table(
            ['Name', 'Slug', 'Declared', 'Installed', 'Migrations', 'Pending', 'Upgrade'],
            $rows
        );

        return self::SUCCESS;
    }

    private function renderSingle(string $slug): int
    {
        if (!AddonManager::exists($slug)) {
            $this->error("Addon introuvable: {$slug}");
            return self::FAILURE;
        }

        $status = AddonMigrationRunner::statusForAddon($slug);

        $this->table(
            ['Field', 'Value'],
            [
                ['Name', $status['name']],
                ['Slug', $status['slug']],
                ['Declared version', $status['declared_version'] !== '' ? $status['declared_version'] : 'n/a'],
                ['Installed version', $status['installed_version'] !== '' ? $status['installed_version'] : 'n/a'],
                ['Has migrations', $status['has_migrations'] ? 'yes' : 'no'],
                ['Has pending migrations', $status['has_pending'] ? 'yes' : 'no'],
                ['Has version upgrade', $status['has_version_upgrade'] ? 'yes' : 'no'],
                ['Migrations path', $status['migrations_path'] !== '' ? $status['migrations_path'] : 'n/a'],
            ]
        );

        return self::SUCCESS;
    }
}
