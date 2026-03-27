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
        public readonly string $html,
        public readonly string $text = '',
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

        return $message->html($this->html !== '' ? $this->html : nl2br(e($this->text)));
    }
}
