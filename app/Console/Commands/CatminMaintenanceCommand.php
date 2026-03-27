<?php

namespace App\Console\Commands;

use App\Services\SettingService;
use Illuminate\Console\Command;

class CatminMaintenanceCommand extends Command
{
    protected $signature = 'catmin:maintenance {state=status : on|off|status}';

    protected $description = 'Active/desactive le mode maintenance CATMIN (frontend)';

    public function handle(): int
    {
        $state = strtolower((string) $this->argument('state'));

        if (!in_array($state, ['on', 'off', 'status'], true)) {
            $this->error('Etat invalide. Utiliser: on, off, status.');
            return self::FAILURE;
        }

        if ($state === 'status') {
            $enabled = filter_var(SettingService::get('system.maintenance_mode', false), FILTER_VALIDATE_BOOL);
            $this->line('CATMIN maintenance: ' . ($enabled ? 'ON' : 'OFF'));
            $this->line('Laravel native maintenance: ' . (app()->isDownForMaintenance() ? 'ON' : 'OFF'));
            return self::SUCCESS;
        }

        $enabled = $state === 'on';
        SettingService::put(
            'system.maintenance_mode',
            $enabled,
            'boolean',
            'system',
            'Mode maintenance frontend CATMIN',
            false
        );

        $this->info('CATMIN maintenance ' . ($enabled ? 'active' : 'desactive') . '.');

        return self::SUCCESS;
    }
}
