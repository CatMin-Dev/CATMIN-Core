<?php

namespace App\Console\Commands;

use App\Services\AnalyticsService;
use Illuminate\Console\Command;

class CatminAnalyticsPruneCommand extends Command
{
    protected $signature = 'catmin:analytics:prune';

    protected $description = 'Purge les evenements analytics au dela de la retention configuree';

    public function __construct(private readonly AnalyticsService $analyticsService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $deleted = $this->analyticsService->prune();
        $this->info('Purge analytics terminee. Evenements supprimes: ' . $deleted);

        return self::SUCCESS;
    }
}
