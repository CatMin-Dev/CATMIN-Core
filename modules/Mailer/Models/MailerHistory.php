<?php

namespace Modules\Mailer\Models;

use Illuminate\Database\Eloquent\Model;

class MailerHistory extends Model
{
    protected $table = 'mailer_history';

    protected $fillable = [
        'recipient',
        'recipient_name',
        'subject',
        'template_code',
        'driver',
        'status',
        'variables_json',
        'body_html',
        'body_text',
        'queued_at',
        'sent_at',
        'failed_at',
        'attempts',
        'is_test',
        'trigger_source',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'variables_json' => 'array',
            'queued_at' => 'datetime',
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
            'is_test' => 'boolean',
        ];
    }
}
