<?php

namespace App\Console\Commands;

use App\Services\AnalyticsService;
use Illuminate\Console\Command;

class CatminAnalyticsDemoSeedCommand extends Command
{
    protected $signature = 'catmin:analytics:demo-seed {--count=20 : Nombre d evenements a generer}';

    protected $description = 'Genere des evenements analytics de demonstration (non sensibles)';

    public function __construct(private readonly AnalyticsService $analyticsService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $count = max(1, (int) $this->option('count'));

        $events = [
            ['admin.module.opened', 'admin', 'opened'],
            ['page.created', 'content', 'created'],
            ['page.published', 'content', 'published'],
            ['builder.opened', 'content', 'opened'],
            ['builder.saved', 'content', 'saved'],
            ['media.uploaded', 'content', 'uploaded'],
            ['docs.search.performed', 'docs', 'search'],
            ['queue.retry.triggered', 'ops', 'retry'],
            ['cron.manual.triggered', 'ops', 'triggered'],
            ['mailer.test.sent', 'ops', 'sent'],
        ];

        for ($i = 0; $i < $count; $i++) {
            $pick = $events[array_rand($events)];
            $failed = random_int(1, 100) <= 15;

            $this->analyticsService->track(
                eventName: $pick[0],
                domain: $pick[1],
                action: $pick[2],
                status: $failed ? 'failed' : 'success',
                context: [
                    'source' => 'demo-seed',
                    'iteration' => $i + 1,
                ],
                metadata: [
                    'latency_ms' => random_int(20, 900),
                ]
            );
        }

        $this->info("{$count} evenements demo analytics generes.");

        return self::SUCCESS;
    }
}
