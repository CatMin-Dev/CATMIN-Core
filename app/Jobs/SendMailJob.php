<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendMailJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** Maximum attempts before the job is considered failed. */
    public int $tries = 3;

    /** Timeout in seconds. */
    public int $timeout = 30;

    public function __construct(
        public readonly string $to,
        public readonly string $subject,
        public readonly string $body,
        public readonly string $from = '',
    ) {}

    public function handle(): void
    {
        $from = $this->from ?: config('mail.from.address', 'no-reply@catmin.local');

        Mail::raw($this->body, function (\Illuminate\Mail\Message $message) use ($from): void {
            $message
                ->to($this->to)
                ->from($from)
                ->subject($this->subject);
        });
    }
}
