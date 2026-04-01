<?php

namespace App\Console\Commands;

use App\Services\Analytics;
use App\Services\RecoveryEngineService;
use Illuminate\Console\Command;

class CatminRecoveryRunCommand extends Command
{
    protected $signature = 'catmin:recovery:run
        {--no-maintenance : Ne pas activer le mode maintenance}
        {--no-code-rollback : Ne pas restaurer le code via git tag}
        {--no-backup-restore : Ne pas restaurer le dernier backup update}';

    protected $description = 'Execute le moteur de recovery systeme (rollback code, DB/backup restore, healthcheck)';

    public function __construct(private readonly RecoveryEngineService $recoveryEngineService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $result = $this->recoveryEngineService->run([
            'maintenance_mode' => !(bool) $this->option('no-maintenance'),
            'rollback_code' => !(bool) $this->option('no-code-rollback'),
            'restore_backup' => !(bool) $this->option('no-backup-restore'),
        ]);

        foreach (($result['steps'] ?? []) as $step) {
            $ok = (bool) ($step['ok'] ?? false);
            $prefix = $ok ? '[OK]' : '[KO]';
            $this->line(sprintf('%s %s - %s', $prefix, (string) ($step['name'] ?? 'step'), (string) ($step['details'] ?? '')));
        }

        Analytics::track('recovery.run', 'ops', 'recovery', (($result['ok'] ?? false) ? 'success' : 'failed'));

        $this->line((string) ($result['message'] ?? 'Recovery terminee.'));

        return (($result['ok'] ?? false) === true) ? self::SUCCESS : self::FAILURE;
    }
}
