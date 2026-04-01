<?php

namespace App\Console\Commands;

use App\Services\AddonManager;
use Illuminate\Console\Command;

class CatminAddonsListCommand extends Command
{
    protected $signature = 'catmin:addons:list {--json : sortie JSON complete}';

    protected $description = 'Lister les addons CATMIN';

    public function handle(): int
    {
        $addons = AddonManager::all()->map(fn ($a) => [
            'name' => (string) ($a->name ?? $a->slug),
            'slug' => (string) $a->slug,
            'version' => (string) ($a->version ?? 'n/a'),
            'status' => (bool) ($a->enabled ?? false) ? 'enabled' : 'disabled',
            'requires_core' => (bool) ($a->requires_core ?? true),
        ])->values();

        if ((bool) $this->option('json')) {
            $this->line(json_encode([
                'ok' => true,
                'summary' => [
                    'total' => $addons->count(),
                    'enabled' => $addons->where('status', 'enabled')->count(),
                    'disabled' => $addons->where('status', 'disabled')->count(),
                ],
                'addons' => $addons->all(),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $rows = $addons->map(fn ($a) => [
            (string) $a['name'],
            (string) $a['slug'],
            (string) $a['version'],
            (string) $a['status'],
            (bool) $a['requires_core'] ? 'yes' : 'no',
        ])->toArray();

        $this->table(['Name', 'Slug', 'Version', 'Status', 'Requires Core'], $rows);

        return self::SUCCESS;
    }
}
