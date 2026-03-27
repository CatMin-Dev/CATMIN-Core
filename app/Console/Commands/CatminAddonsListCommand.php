<?php

namespace App\Console\Commands;

use App\Services\AddonManager;
use Illuminate\Console\Command;

class CatminAddonsListCommand extends Command
{
    protected $signature = 'catmin:addons:list';

    protected $description = 'Lister les addons CATMIN';

    public function handle(): int
    {
        $rows = AddonManager::all()->map(fn ($a) => [
            (string) ($a->name ?? $a->slug),
            (string) $a->slug,
            (string) ($a->version ?? 'n/a'),
            (bool) ($a->enabled ?? false) ? 'enabled' : 'disabled',
            (bool) ($a->requires_core ?? true) ? 'yes' : 'no',
        ])->toArray();

        $this->table(['Name', 'Slug', 'Version', 'Status', 'Requires Core'], $rows);

        return self::SUCCESS;
    }
}
