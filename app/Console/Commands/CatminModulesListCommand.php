<?php

namespace App\Console\Commands;

use App\Services\ModuleManager;
use Illuminate\Console\Command;

class CatminModulesListCommand extends Command
{
    protected $signature = 'catmin:modules:list';

    protected $description = 'Lister les modules CATMIN';

    public function handle(): int
    {
        $rows = ModuleManager::all()->map(fn ($m) => [
            (string) ($m->name ?? $m->slug),
            (string) $m->slug,
            (string) ($m->version ?? 'n/a'),
            (bool) ($m->enabled ?? false) ? 'enabled' : 'disabled',
            implode(', ', (array) ($m->depends ?? [])) ?: '-',
        ])->toArray();

        $this->table(['Name', 'Slug', 'Version', 'Status', 'Depends'], $rows);

        return self::SUCCESS;
    }
}
