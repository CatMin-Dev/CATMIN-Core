<?php

namespace Modules\Mailer\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TemplatedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $mailSubject,
        public readonly string $htmlContent,
        public readonly string $textContent = '',
        public readonly ?string $fromEmail = null,
        public readonly ?string $fromName = null,
        public readonly ?string $replyToEmail = null,
    ) {
    }

    public function build(): static
    {
        $message = $this->subject($this->mailSubject);

        if ($this->fromEmail) {
            $message->from($this->fromEmail, $this->fromName ?? null);
        }

        if ($this->replyToEmail) {
            $message->replyTo($this->replyToEmail);
        }

        return $message->html($this->htmlContent !== '' ? $this->htmlContent : nl2br(e($this->textContent)));
    }
}
