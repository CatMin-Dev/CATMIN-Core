<?php

namespace App\Services;

class UpdatePlannerService
{
    /**
     * @return array{modules: array<int, array<string, string>>, addons: array<int, array<string, string>>}
     */
    public static function collectPendingUpgrades(): array
    {
        $modules = [];
        foreach (ModuleManager::all() as $module) {
            $slug = (string) $module->slug;
            $installed = ModuleMigrationRunner::getInstalledVersion($slug);
            $current = VersioningService::normalize((string) ($module->version ?? ''));

            if ($installed !== '' && VersioningService::isUpgrade($installed, $current)) {
                $modules[] = [
                    'name' => (string) ($module->name ?? $slug),
                    'slug' => $slug,
                    'installed' => VersioningService::normalize($installed),
                    'target' => $current,
                ];
            }
        }

        $addons = [];
        foreach (AddonManager::all() as $addon) {
            $slug = (string) $addon->slug;
            $installed = AddonMigrationRunner::getInstalledVersion($slug);
            $current = VersioningService::normalize((string) ($addon->version ?? ''));

            if ($installed !== '' && VersioningService::isUpgrade($installed, $current)) {
                $addons[] = [
                    'name' => (string) ($addon->name ?? $slug),
                    'slug' => $slug,
                    'installed' => VersioningService::normalize($installed),
                    'target' => $current,
                ];
            }
        }

        return [
            'modules' => $modules,
            'addons' => $addons,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function buildManualWorkflow(): array
    {
        return [
            '1) Sauvegarde DB + fichiers (storage, uploads, .env).',
            '2) Récupérer le code cible depuis GitHub (tag/branche).',
            '3) Installer les dépendances: composer install --no-dev --optimize-autoloader',
            '4) Vérifier le plan: php artisan catmin:update:plan',
            '5) Appliquer update assisté: php artisan catmin:update:apply',
            '6) Vérifier l’admin et les logs puis relancer les workers/cron.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function plan(): array
    {
        return [
            'strategy' => [
                'core' => 'Mise à jour via GitHub (pull/tag), jamais auto-update SaaS.',
                'modules' => 'Version dans module.json + migrations par module.',
                'addons' => 'Version dans addon.json + migrations par addon.',
                'mode' => 'Workflow manuel/assisté projet par projet.',
            ],
            'workflow' => self::buildManualWorkflow(),
            'pending_upgrades' => self::collectPendingUpgrades(),
            'migration_collisions' => MigrationCollisionService::detectBasenameCollisions(),
        ];
    }
}
