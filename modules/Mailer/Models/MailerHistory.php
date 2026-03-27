<?php

namespace Modules\Mailer\Models;

use Illuminate\Database\Eloquent\Model;

class MailerHistory extends Model
{
    protected $table = 'mailer_history';

    protected $fillable = [
        'recipient',
        'subject',
        'template_code',
        'status',
        'sent_at',
        'error_message',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];
}
