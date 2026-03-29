<?php

namespace Modules\Mailer\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Mailer\Services\MailerAdminService;

class SendTemplatedMailJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $timeout = 30;

    /** @var array<int, int> */
    public array $backoff = [60, 300, 900];

    public function __construct(public readonly int $historyId)
    {
    }

    public function handle(MailerAdminService $mailerAdminService): void
    {
        $mailerAdminService->deliverHistory($this->historyId);
    }

    /**
     * @return array<int, string>
     */
    public function tags(): array
    {
        return ['mailer', 'mailer-history:' . $this->historyId];
    }
}
