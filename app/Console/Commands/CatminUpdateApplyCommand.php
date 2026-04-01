<?php

namespace App\Console\Commands;

use App\Services\MigrationCollisionService;
use App\Services\MigrationSafetyService;
use Illuminate\Console\Command;

class CatminUpdateApplyCommand extends Command
{
    protected $signature = 'catmin:update:apply
        {--dry-run : Affiche seulement les actions}
        {--skip-core-migrate : Ignore php artisan migrate}';

    protected $description = 'Applique la phase assistée de mise à jour (migrations core/modules/addons + clear caches)';

    public function __construct(private readonly MigrationSafetyService $migrationSafetyService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $skipCoreMigrate = (bool) $this->option('skip-core-migrate');

        $collisions = MigrationCollisionService::detectBasenameCollisions();
        if (!empty($collisions)) {
            $this->error('Arrêt: collisions de noms de migrations détectées. Corrigez avant update.');
            return self::FAILURE;
        }

        $actions = [
            $skipCoreMigrate ? null : 'php artisan migrate --force',
            'php artisan catmin:migrate:extensions',
            'php artisan cache:clear',
            'php artisan config:clear',
            'php artisan view:clear',
            'php artisan route:clear',
        ];

        $actions = array_values(array_filter($actions));

        $this->info('Plan apply:');
        foreach ($actions as $action) {
            $this->line('- ' . $action);
        }

        if ($dryRun) {
            $this->comment('Dry-run: aucune commande exécutée.');
            return self::SUCCESS;
        }

        $result = $this->migrationSafetyService->run([
            'dry_run' => false,
            'skip_core_migrate' => $skipCoreMigrate,
            'rollback_on_fail' => true,
            'backup_name' => 'update-apply-' . now()->format('Ymd-His'),
        ]);

        $this->line((string) ($result['message'] ?? 'Execution terminee.'));

        if (!empty($result['backup_dir'])) {
            $this->line('Backup pre-update: ' . $result['backup_dir']);
        }

        if (($result['ok'] ?? false) !== true) {
            if (!empty($result['rollback']['message'])) {
                $this->warn('Rollback: ' . (string) $result['rollback']['message']);
            }

            return self::FAILURE;
        }

        $this->info('Mise à jour assistée terminée. Vérifiez ensuite l\'admin et les logs.');

        return self::SUCCESS;
    }
}
