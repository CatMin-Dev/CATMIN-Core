<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Webhooks\Models\WebhookDelivery;
use Modules\Webhooks\Services\WebhookDispatcher;

/**
 * Process pending webhook retry deliveries.
 *
 * Picks up deliveries in status `retrying` whose next_retry_at has elapsed,
 * and re-dispatches them. Deliveries that exhaust max_attempts are moved to
 * the dead-letter queue by WebhookDelivery::markFailedWithRetry().
 */
class WebhookRetryCommand extends Command
{
    protected $signature = 'webhooks:process-retries
                            {--limit=50 : Maximum number of deliveries to process in one run}';

    protected $description = 'Process pending webhook retry deliveries (exponential backoff)';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');

        /** @var \Illuminate\Database\Eloquent\Collection<int, WebhookDelivery> $deliveries */
        $deliveries = WebhookDelivery::query()
            ->where('status', 'retrying')
            ->where('next_retry_at', '<=', now())
            ->with('webhook')
            ->limit($limit)
            ->get();

        if ($deliveries->isEmpty()) {
            $this->line('No pending retries.');
            return self::SUCCESS;
        }

        $this->info("Processing {$deliveries->count()} retry delivery/deliveries…");

        $success = 0;
        $failed = 0;
        $deadLettered = 0;

        foreach ($deliveries as $delivery) {
            $webhook = $delivery->webhook;

            if (!$webhook || !$webhook->isActive()) {
                $delivery->markFailed('Webhook not found or inactive');
                $failed++;
                continue;
            }

            try {
                WebhookDispatcher::retryDelivery($delivery);
                $delivery->refresh();

                if ($delivery->status === 'success') {
                    $success++;
                } elseif ($delivery->status === 'dead_letter') {
                    $deadLettered++;
                } else {
                    $failed++;
                }
            } catch (\Throwable $e) {
                $delivery->markFailedWithRetry('Retry command exception: ' . $e->getMessage());
                $delivery->refresh();
                if ($delivery->isDeadLetter()) {
                    $deadLettered++;
                } else {
                    $failed++;
                }
            }
        }

        $this->info("Done — success: {$success}, retrying: {$failed}, dead-lettered: {$deadLettered}");

        return self::SUCCESS;
    }
}
