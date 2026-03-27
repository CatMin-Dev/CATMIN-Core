<?php

namespace App\Console\Commands;

use App\Services\ModuleManager;
use Illuminate\Console\Command;

class CatminModuleEnableCommand extends Command
{
    protected $signature = 'catmin:module:enable {slug : Module slug}';

    protected $description = 'Activer un module CATMIN';

    public function handle(): int
    {
        $slug = (string) $this->argument('slug');

        $check = ModuleManager::canEnable($slug);
        if (!$check['allowed']) {
            $this->error($check['message']);
            return self::FAILURE;
        }

        if (!ModuleManager::enable($slug)) {
            $this->error('Activation echouee.');
            return self::FAILURE;
        }

        $this->info("Module '{$slug}' active.");

        return self::SUCCESS;
    }
}
