<?php

namespace App\Console\Commands;

use App\Services\MigrationCollisionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class CatminUpdateApplyCommand extends Command
{
    protected $signature = 'catmin:update:apply
        {--dry-run : Affiche seulement les actions}
        {--skip-core-migrate : Ignore php artisan migrate}';

    protected $description = 'Applique la phase assistée de mise à jour (migrations core/modules/addons + clear caches)';

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

        if (!$skipCoreMigrate) {
            $this->line('> migrate --force');
            Artisan::call('migrate', ['--force' => true]);
        }

        $this->line('> catmin:migrate:extensions');
        $exit = Artisan::call('catmin:migrate:extensions');
        if ($exit !== 0) {
            $this->error('catmin:migrate:extensions a échoué.');
            return self::FAILURE;
        }

        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('view:clear');
        Artisan::call('route:clear');

        $this->info('Mise à jour assistée terminée. Vérifiez ensuite l’admin et les logs.');

        return self::SUCCESS;
    }
}
