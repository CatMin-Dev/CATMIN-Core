<?php

namespace App\Console\Commands;

use App\Services\ModuleManager;
use Illuminate\Console\Command;

class CatminModuleDisableCommand extends Command
{
    protected $signature = 'catmin:module:disable {slug : Module slug}';

    protected $description = 'Desactiver un module CATMIN';

    public function handle(): int
    {
        $slug = (string) $this->argument('slug');

        $check = ModuleManager::canDisable($slug);
        if (!$check['allowed']) {
            $this->error($check['message']);
            return self::FAILURE;
        }

        if (!ModuleManager::disable($slug)) {
            $this->error('Desactivation echouee.');
            return self::FAILURE;
        }

        $this->info("Module '{$slug}' desactive.");

        return self::SUCCESS;
    }
}
