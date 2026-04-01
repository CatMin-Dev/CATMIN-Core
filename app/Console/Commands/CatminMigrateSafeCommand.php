<?php

namespace App\Console\Commands;

use App\Services\MigrationSafetyService;
use Illuminate\Console\Command;

class CatminMigrateSafeCommand extends Command
{
    protected $signature = 'catmin:migrate:safe
        {--dry-run : Affiche seulement les actions}
        {--skip-core-migrate : Ignore php artisan migrate}
        {--no-rollback : Ne pas tenter de rollback auto si echec}';

    protected $description = 'Execute les migrations en mode securise (backup auto, rollback sur echec, logs)';

    public function __construct(private readonly MigrationSafetyService $migrationSafetyService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $result = $this->migrationSafetyService->run([
            'dry_run' => (bool) $this->option('dry-run'),
            'skip_core_migrate' => (bool) $this->option('skip-core-migrate'),
            'rollback_on_fail' => !(bool) $this->option('no-rollback'),
            'backup_name' => 'safe-migrate-' . now()->format('Ymd-His'),
        ]);

        $this->info((string) ($result['message'] ?? 'Execution terminee.'));

        if (($result['dry_run'] ?? false) === true) {
            $this->line('Actions planifiees:');
            foreach (($result['actions'] ?? []) as $action) {
                $this->line('- ' . $action);
            }
            return self::SUCCESS;
        }

        if (!empty($result['backup_dir'])) {
            $this->line('Backup: ' . $result['backup_dir']);
        }

        if (($result['ok'] ?? false) !== true) {
            if (!empty($result['error'])) {
                $this->error('Erreur: ' . $result['error']);
            }
            if (!empty($result['rollback']['message'])) {
                $this->warn('Rollback: ' . $result['rollback']['message']);
            }
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
